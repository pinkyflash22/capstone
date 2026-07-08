@extends('barangay.layouts.app')

@php
    $totalRiskAreas = (int) $riskAreas->count();
    $pendingRiskAreas = (int) $riskAreas->where('status', 'pending')->count();
    $verifiedRiskAreas = (int) $riskAreas->where('status', 'verified')->count();
    $resolvedRiskAreas = (int) $riskAreas->where('status', 'resolved')->count();
    $highAssessmentCount = (int) $riskAreas->where('assessment_level', 'high')->count();
    $reviewPendingCount = (int) $riskAreas->where('review_status', 'pending')->count();
    $riskAreaCrudPayload = $riskAreas->map(function ($riskArea) {
        return [
            'id' => $riskArea->risk_area_id,
            'title' => $riskArea->marker?->title,
            'description' => $riskArea->marker?->description,
            'lat' => $riskArea->marker?->lat,
            'lng' => $riskArea->marker?->lng,
            'entry_type' => $riskArea->entry_type ?? 'risk',
            'street_or_sitio' => $riskArea->street_or_sitio,
            'station_id' => $riskArea->station_id,
            'risk_type' => $riskArea->risk_type,
            'hazard_category' => $riskArea->hazard_category,
            'hazard_type' => $riskArea->hazard_type,
            'condition_code' => $riskArea->condition_code ?? $riskArea->mode,
            'condition_label' => $riskArea->condition_label,
            'notes' => $riskArea->remarks,
            'population_density_level' => $riskArea->population_density_level,
            'building_density_level' => $riskArea->building_density_level,
            'road_accessibility' => $riskArea->road_accessibility,
            'assessment_level' => $riskArea->assessment_level,
            'distance_to_hydrant' => $riskArea->distance_to_hydrant,
            'distance_to_water_source' => $riskArea->distance_to_water_source,
            'estimated_affected_households' => $riskArea->estimated_affected_households,
            'risk_score' => $riskArea->risk_score,
            'nearby_establishments' => $riskArea->nearby_establishments,
            'assessment_description' => $riskArea->assessment_description,
            'remarks' => $riskArea->remarks,
            'status' => $riskArea->status,
            'review_status' => $riskArea->review_status ?? 'pending',
            'review_type' => $riskArea->review_type ?? 'create',
            'near_hydrant' => (bool) $riskArea->near_hydrant,
            'near_water_source' => (bool) $riskArea->near_water_source,
            'blocked_hydrant' => (bool) $riskArea->blocked_hydrant,
            'informal_settlement' => (bool) $riskArea->informal_settlement,
            'cooking_fire_prone_activity' => (bool) $riskArea->cooking_fire_prone_activity,
            'combustible_materials_present' => (bool) $riskArea->combustible_materials_present,
            'poor_waste_disposal' => (bool) $riskArea->poor_waste_disposal,
            'previous_fire_prone_location' => (bool) $riskArea->previous_fire_prone_location,
            'report_kind' => $riskArea->entry_type ?? 'risk',
        ];
    })->values();
    $hazardsPageHallPayload = $hall && $hall->marker ? [
        'title' => $hall->hall_name ?: 'Barangay Hall',
        'lat' => $hall->marker->lat,
        'lng' => $hall->marker->lng,
    ] : null;
@endphp

@section('active_nav', 'hazards')
@section('page_title', 'Hazards & Risks')
@section('page_subtitle', 'Hazard and risk records')
@section('hide_hero', '1')

@section('top_bar_actions')
    <button type="button" class="portal-report-fire-btn !bg-white !text-orange-700 !border !border-orange-200 !shadow-none" data-risk-report-open="hazard">
        <i class="fa-solid fa-bolt"></i>
        <span>Report Hazard</span>
    </button>
    <button type="button" class="portal-report-fire-btn" data-risk-report-open="risk">
        <i class="fa-solid fa-layer-group"></i>
        <span>Report Risk</span>
    </button>
@endsection

@section('content')
    <style>
        .risk-modal-root {
            position: fixed;
            inset: 0;
            z-index: 120;
            display: none;
        }

        .risk-modal-root.is-open {
            display: block;
        }

        .risk-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.56);
            backdrop-filter: blur(4px);
        }

        .risk-modal-panel {
            position: relative;
            width: min(1120px, calc(100vw - 32px));
            max-height: calc(100vh - 32px);
            margin: 16px auto;
            border-radius: 30px;
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.24);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .risk-modal-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 26px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #fffbeb 100%);
        }

        .risk-modal-title {
            margin: 6px 0 0;
            color: #0f172a;
            font-size: 1.4rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .risk-modal-copy {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.65;
            max-width: 64ch;
        }

        .risk-modal-close {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #fff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .risk-modal-body {
            padding: 22px 26px 26px;
            overflow: auto;
        }

        .risk-mode-toggle {
            display: inline-flex;
            gap: 8px;
            padding: 6px;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid rgba(245, 158, 11, 0.16);
        }

        .risk-mode-btn {
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            border: 0;
            background: transparent;
            color: #9a3412;
            font-size: 0.84rem;
            font-weight: 800;
        }

        .risk-mode-btn.is-active {
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: #fff;
            box-shadow: 0 10px 24px rgba(234, 88, 12, 0.18);
        }

        .risk-form-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
        }

        .risk-form-stack {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .risk-form-card {
            background: #fff;
            border: 1px solid rgba(226, 232, 240, 0.92);
            border-radius: 24px;
            padding: 20px;
        }

        .risk-form-card h4 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .risk-form-card p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.87rem;
            line-height: 1.6;
        }

        .risk-inline-hint {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .risk-inline-hint i {
            color: #f59e0b;
        }

        .risk-form-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .risk-form-fields .full {
            grid-column: 1 / -1;
        }

        .risk-form-field label {
            display: block;
            margin-bottom: 6px;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .risk-form-field input,
        .risk-form-field select,
        .risk-form-field textarea {
            width: 100%;
            min-height: 44px;
            border-radius: 14px;
            border: 1px solid rgba(203, 213, 225, 0.92);
            background: #fff;
            padding: 11px 13px;
            color: #0f172a;
            font-size: 0.9rem;
        }

        .risk-form-field textarea {
            min-height: 110px;
            resize: vertical;
        }

        .risk-chip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .risk-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #fff;
            padding: 0 12px;
            color: #334155;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .risk-chip input {
            width: 16px;
            height: 16px;
        }

        .risk-map-picker {
            margin-top: 16px;
            border-radius: 20px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            overflow: hidden;
            min-height: 340px;
        }

        .risk-form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-top: 18px;
        }

        .risk-form-note {
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .risk-form-submit {
            min-height: 46px;
            padding: 0 18px;
            border-radius: 999px;
            border: 0;
            background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 800;
            box-shadow: 0 12px 24px rgba(234, 88, 12, 0.18);
        }

        .risk-flow-cues {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .risk-flow-cue {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(251, 191, 36, 0.18);
            background: #fffaf0;
            color: #9a3412;
            font-size: 0.76rem;
            font-weight: 800;
        }

        .risk-review-card {
            margin-top: 18px;
            border-radius: 20px;
            border: 1px solid rgba(251, 191, 36, 0.18);
            background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
            padding: 14px;
        }

        .risk-review-card__grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .risk-review-card__item {
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: rgba(255, 255, 255, 0.88);
            padding: 12px;
        }

        .risk-review-card__item span {
            display: block;
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .risk-review-card__item strong {
            display: block;
            margin-top: 6px;
            color: #0f172a;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .risk-mode-section {
            display: none;
        }

        .risk-mode-section.is-active {
            display: block;
        }

        .risk-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(92px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }

        .risk-photo-thumb {
            display: block;
            aspect-ratio: 1 / 1;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #fff7ed;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
        }

        .risk-photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .risk-photo-caption {
            margin-top: 8px;
            color: #64748b;
            font-size: 0.76rem;
            line-height: 1.45;
        }

        .risk-table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 14px 0 16px;
            flex-wrap: wrap;
        }

        .risk-table-filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .risk-table-filter,
        .risk-table-search {
            min-height: 42px;
            border-radius: 14px;
            border: 1px solid rgba(203, 213, 225, 0.92);
            background: #fff;
            color: #0f172a;
            font-size: 0.88rem;
            padding: 0 14px;
        }

        .risk-table-search {
            min-width: min(320px, 100%);
        }

        .risk-table-empty-row td {
            color: #64748b;
            text-align: center;
            padding: 18px;
        }

        .hazards-command {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.85fr);
            gap: 18px;
            align-items: start;
        }

        .hazards-command__copy {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .hazards-command__copy h3 {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 900;
            letter-spacing: -0.03em;
            color: #0f172a;
        }

        .hazards-command__copy p {
            margin: 0;
            color: #64748b;
            font-size: 0.98rem;
            line-height: 1.7;
            max-width: 62ch;
        }

        .hazards-inline-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hazards-inline-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid rgba(251, 191, 36, 0.18);
            background: #fffaf0;
            color: #9a3412;
            font-size: 0.82rem;
            font-weight: 800;
        }

        .hazards-command__aside {
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: linear-gradient(180deg, #fffdfa 0%, #ffffff 100%);
            padding: 18px;
        }

        .hazards-command__aside h4 {
            margin: 0;
            color: #0f172a;
            font-size: 1.02rem;
            font-weight: 900;
        }

        .hazards-command__aside p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .hazards-command__aside .hero-actions {
            margin-top: 14px;
        }

        .hazards-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .hazards-table thead th {
            padding: 12px 14px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(226, 232, 240, 0.92);
        }

        .hazards-table tbody td {
            padding: 14px;
            vertical-align: top;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            color: #334155;
            font-size: 0.9rem;
        }

        .hazards-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .hazards-table__title {
            color: #0f172a;
            font-weight: 800;
            line-height: 1.4;
        }

        .hazards-table__meta {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.78rem;
        }

        .hazards-pill {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .hazards-pill--type {
            background: #fff7ed;
            color: #c2410c;
        }

        .hazards-pill--status {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .hazards-pill--review {
            background: #f8fafc;
            color: #475569;
        }

        .hazards-location {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .hazards-photo-strip {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .hazards-photo-strip .risk-photo-thumb {
            width: 58px;
            min-width: 58px;
            aspect-ratio: 1 / 1;
            border-radius: 14px;
        }

        .hazards-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 1024px) {
            .risk-form-grid {
                grid-template-columns: 1fr;
            }

            .hazards-command {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .risk-modal-panel {
                width: calc(100vw - 16px);
                max-height: calc(100dvh - 16px);
                margin: 8px auto;
                border-radius: 24px 24px 20px 20px;
            }

            .risk-modal-header,
            .risk-modal-body {
                padding-left: 18px;
                padding-right: 18px;
            }

            .risk-modal-header {
                padding-top: 18px;
                padding-bottom: 16px;
            }

            .risk-modal-body {
                padding-bottom: calc(22px + env(safe-area-inset-bottom, 0px));
            }

            .risk-form-fields,
            .risk-chip-grid,
            .risk-review-card__grid {
                grid-template-columns: 1fr;
            }

            .risk-form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .risk-flow-cues {
                gap: 8px;
            }

            .risk-flow-cue {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>

    @if (session('success'))
        <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">There was a problem saving the hazard or risk report.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="metric-grid">
        <article class="metric-card">
            <div class="metric-label">Total Entries</div>
            <div class="metric-value">{{ $totalRiskAreas }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Pending Verification</div>
            <div class="metric-value">{{ $pendingRiskAreas }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Verified</div>
            <div class="metric-value">{{ $verifiedRiskAreas }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">High Assessment</div>
            <div class="metric-value">{{ $highAssessmentCount }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Awaiting Review</div>
            <div class="metric-value">{{ $reviewPendingCount }}</div>
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="hazards-command">
                <div class="hazards-command__copy">
                    <div class="panel-eyebrow">Submission Center</div>
                    <h3>Create and review field records.</h3>
                    <p>Use hazard reports for a specific unsafe finding. Use risk reports for wider area fire exposure.</p>
                    <div class="hazards-inline-tags">
                        <span class="hazards-inline-tag"><i class="fa-solid fa-bolt"></i> Specific hazards</span>
                        <span class="hazards-inline-tag"><i class="fa-solid fa-layer-group"></i> Area risks</span>
                        <span class="hazards-inline-tag"><i class="fa-solid fa-clipboard-check"></i> Review tracked</span>
                    </div>
                </div>

                <div class="hazards-command__aside">
                    <h4>Quick actions</h4>
                    <p>Start a new field report without opening extra dashboard cards.</p>
                    <div class="hero-actions">
                        <button type="button" class="hero-action-btn hero-action-btn--primary" data-risk-report-open="hazard">
                            <i class="fa-solid fa-bolt"></i>
                            <span>Report Hazard</span>
                        </button>
                        <button type="button" class="hero-action-btn hero-action-btn--primary" data-risk-report-open="risk">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Report Risk</span>
                        </button>
                    </div>
                </div>
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Checklist Pressure</div>
            <h3 class="panel-title">Open mitigation tasks</h3>
            @if($predictionChecklists->isEmpty())
                <div class="empty-state">No hazard mitigation checklist entries are assigned to this barangay yet.</div>
            @else
                <div class="list-stack">
                    @foreach($predictionChecklists->take(6) as $item)
                        <div class="list-item">
                            <h4>{{ $item->title }}</h4>
                            <p>{{ $item->ai_output }}</p>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}">{{ strtoupper($item->priority_level) }}</span>
                                <span class="tag {{ $item->status }}">{{ str_replace('_', ' ', strtoupper($item->status)) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Recent Submissions</div>
            <h3 class="panel-title">Latest hazard and risk records</h3>
            @if($riskAreas->isEmpty())
                <div class="empty-state">No barangay hazard or risk submissions are available yet.</div>
            @else
                <div class="risk-table-toolbar">
                    <div class="risk-table-filter-group">
                        <select id="riskTableTypeFilter" class="risk-table-filter" aria-label="Filter submissions by type">
                            <option value="all">All entries</option>
                            <option value="hazard">Hazards only</option>
                            <option value="risk">Risks only</option>
                        </select>
                        <select id="riskTableStatusFilter" class="risk-table-filter" aria-label="Filter submissions by status">
                            <option value="all">All statuses</option>
                            <option value="PENDING">Pending</option>
                            <option value="VERIFIED">Verified</option>
                            <option value="RESOLVED">Resolved</option>
                        </select>
                    </div>
                    <div class="risk-table-filter-group">
                        <input id="riskTableSearchInput" type="search" class="risk-table-search" placeholder="Search title, type, status, review, or location">
                    </div>
                </div>
                <div class="table-wrap">
                    <table class="hazards-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Review</th>
                                <th>Location</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="riskTableBody">
                            @foreach($riskAreas->take(10) as $riskArea)
                                @php
                                    $entryType = $riskArea->entry_type ?? 'risk';
                                    $typeLabel = $entryType === 'hazard'
                                        ? ($hazardTypeOptions[$riskArea->hazard_category ?? ''] ?? ucwords(str_replace('_', ' ', $riskArea->hazard_type ?: 'general')))
                                        : ($riskTypeOptions[$riskArea->risk_type ?? ''] ?? ucwords(str_replace('_', ' ', $riskArea->risk_type ?: 'general')));
                                    $statusLabel = strtoupper($riskArea->status ?? 'pending');
                                    $reviewLabel = strtoupper(($riskArea->review_type ?? 'create') . ' / ' . ($riskArea->review_status ?? 'pending'));
                                    $locationLabel = $riskArea->street_or_sitio ?: 'General barangay area';
                                @endphp
                                <tr
                                    data-risk-row-id="{{ $riskArea->risk_area_id }}"
                                    data-entry-type="{{ $entryType }}"
                                    data-status="{{ $statusLabel }}"
                                    data-search="{{ strtolower(trim(($riskArea->marker?->title ?? 'Untitled entry') . ' ' . $typeLabel . ' ' . $statusLabel . ' ' . $reviewLabel . ' ' . $locationLabel)) }}"
                                >
                                    <td>{{ optional($riskArea->created_at)->format('M d, Y') }}</td>
                                    <td>
                                        <div class="hazards-table__title">{{ $riskArea->marker?->title ?? 'Untitled entry' }}</div>
                                        <div class="hazards-table__meta">{{ $riskArea->assessment_level ? ucfirst($riskArea->assessment_level) . ' assessment' : 'Awaiting assessment' }}</div>
                                    </td>
                                    <td><span class="hazards-pill hazards-pill--type">{{ $typeLabel }}</span></td>
                                    <td><span class="hazards-pill hazards-pill--status">{{ $statusLabel }}</span></td>
                                    <td><span class="hazards-pill hazards-pill--review">{{ $reviewLabel }}</span></td>
                                    <td>
                                        <div class="hazards-location">
                                            <div>{{ $locationLabel }}</div>
                                        @if($riskArea->photos->isNotEmpty())
                                            <div class="hazards-photo-strip">
                                                @foreach($riskArea->photos->take(3) as $photo)
                                                    <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener" class="risk-photo-thumb">
                                                        <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="hazards-actions">
                                            @if(($riskArea->review_type ?? 'create') !== 'delete')
                                                <button
                                                    type="button"
                                                    class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm"
                                                    data-risk-edit='@json($riskAreaCrudPayload->firstWhere("id", $riskArea->risk_area_id))'
                                                >
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    <span>Edit</span>
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('barangay.risk-map.destroy', $riskArea->risk_area_id) }}" onsubmit="return confirm('Send this record for deletion review?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_to" value="hazards">
                                                <button type="submit" class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm !bg-white !text-red-700 !border !border-red-200 !shadow-none" @disabled(($riskArea->review_type ?? 'create') === 'delete' && ($riskArea->review_status ?? 'pending') === 'pending')>
                                                    <i class="fa-solid fa-trash-can"></i>
                                                    <span>{{ ($riskArea->review_type ?? 'create') === 'delete' && ($riskArea->review_status ?? 'pending') === 'pending' ? 'Delete Pending' : 'Delete' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="riskTableNoResults" class="risk-table-empty-row" style="{{ $riskAreas->isEmpty() ? '' : 'display: none;' }}">
                                <td colspan="7">No hazard or risk records match the current filter.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Historical Context</div>
            <h3 class="panel-title">Recent fire-related local records</h3>
            @if($historicalIncidents->isEmpty())
                <div class="empty-state">No historical hazard records are available yet for this barangay.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Classification</th>
                                <th>Cause</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalIncidents->take(8) as $incident)
                                <tr>
                                    <td>{{ optional($incident->incident_date)->format('M d, Y') }}</td>
                                    <td>{{ $incident->fire_classification ?? 'N/A' }}</td>
                                    <td>{{ $incident->fire_cause ?? 'Pending' }}</td>
                                    <td>{{ $incident->incident_location ?? 'No note' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    <div id="riskReportModal" class="risk-modal-root {{ $errors->any() ? 'is-open' : '' }}" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
        <div class="risk-modal-backdrop" data-risk-report-close></div>
        <div class="risk-modal-panel">
            <div class="risk-modal-header">
                <div>
                    <div class="panel-eyebrow">Barangay Submission</div>
                    <h3 id="riskReportModalTitle" class="risk-modal-title">Report Risk</h3>
                    <p id="riskReportModalCopy" class="risk-modal-copy">Document a broader area-level fire exposure that should be reviewed, verified, and mapped for barangay planning.</p>
                </div>
                <button type="button" class="risk-modal-close" data-risk-report-close aria-label="Close report modal">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="risk-modal-body">
                <div class="risk-mode-toggle">
                    <button type="button" class="risk-mode-btn" data-risk-mode="hazard">Hazard</button>
                    <button type="button" class="risk-mode-btn" data-risk-mode="risk">Risk</button>
                </div>

                <div class="risk-flow-cues" aria-hidden="true">
                    <span class="risk-flow-cue"><i class="fa-solid fa-pen"></i> Basic details</span>
                    <span class="risk-flow-cue"><i class="fa-solid fa-layer-group"></i> Type-specific fields</span>
                    <span class="risk-flow-cue"><i class="fa-solid fa-location-dot"></i> Pin exact location</span>
                </div>

                <form method="POST" action="{{ route('barangay.risk-map.store') }}" enctype="multipart/form-data" class="mt-5">
                    @csrf
                    <input type="hidden" id="riskReportMethodInput" name="_method" value="{{ old('form_mode') === 'edit' ? 'PUT' : 'POST' }}">
                    <input type="hidden" name="return_to" value="hazards">
                    <input type="hidden" name="status" value="{{ old('status', 'pending') }}">
                    <input type="hidden" id="riskReportEntryTypeInput" name="entry_type" value="{{ old('entry_type', old('report_kind', 'risk')) }}">
                    <input type="hidden" id="riskReportKindInput" name="report_kind" value="{{ old('report_kind', 'risk') }}">
                    <input type="hidden" id="riskReportConditionCodeInput" name="condition_code" value="{{ old('condition_code', old('mode')) }}">
                    <input type="hidden" id="riskReportFormMode" name="form_mode" value="{{ old('form_mode', 'create') }}">
                    <input type="hidden" id="riskReportEditingId" name="editing_risk_area_id" value="{{ old('editing_risk_area_id') }}">

                    <div class="risk-form-grid">
                        <div class="risk-form-stack">
                            <section class="risk-form-card">
                                <h4>Details</h4>
                                <div class="risk-inline-hint"><i class="fa-regular fa-circle-info"></i><span>Use short labels here. Put extra context in notes.</span></div>
                                <div class="risk-form-fields">
                                    <div class="full risk-form-field">
                                        <label for="hazard_report_title">Title</label>
                                        <input id="hazard_report_title" name="title" type="text" value="{{ old('title') }}" placeholder="Example: Blocked hydrant beside covered court">
                                    </div>
                                    <div class="full risk-form-field">
                                        <label for="hazard_report_location_description">Location</label>
                                        <textarea id="hazard_report_location_description" name="location_description" placeholder="Landmark, purok, access issue, visible conditions">{{ old('location_description') }}</textarea>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="hazard_report_street">Street / Sitio</label>
                                        <input id="hazard_report_street" name="street_or_sitio" type="text" value="{{ old('street_or_sitio') }}" placeholder="Specific local reference">
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="hazard_report_station">Station</label>
                                        <select id="hazard_report_station" name="station_id">
                                            <option value="">No linked station</option>
                                            @foreach ($stations as $station)
                                                <option value="{{ $station->station_id }}" @selected((string) old('station_id') === (string) $station->station_id)>{{ $station->station_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="full risk-form-field">
                                        <label for="hazard_report_notes">Notes</label>
                                        <textarea id="hazard_report_notes" name="notes" placeholder="Observed conditions, concerns, and context">{{ old('notes') }}</textarea>
                                    </div>
                                    <div class="full risk-form-field">
                                        <label for="hazard_report_photos">Photo evidence</label>
                                        <input id="hazard_report_photos" name="photos[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                </div>
                            </section>

                            <section id="riskModeSection" class="risk-form-card risk-mode-section">
                                <h4>Risk assessment</h4>
                                <div class="risk-inline-hint"><i class="fa-regular fa-circle-info"></i><span>Use this only for wider area exposure, not one isolated unsafe object.</span></div>
                                <div class="risk-form-fields">
                                    <div class="risk-form-field">
                                        <label for="hazard_report_risk_type">Risk type</label>
                                        <select id="hazard_report_risk_type" name="risk_type">
                                            <option value="">Select risk type</option>
                                            @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                <optgroup label="{{ $groupLabel }}">
                                                    @foreach ($groupValues as $value)
                                                        <option value="{{ $value }}" @selected(old('risk_type') === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="hazard_report_risk_condition">Condition</label>
                                        <select id="hazard_report_risk_condition">
                                            <option value="">Select risk condition</option>
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_population_density_level">Population</label>
                                        <select id="risk_population_density_level" name="population_density_level">
                                            <option value="">Select level</option>
                                            @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('population_density_level') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_building_density_level">Buildings</label>
                                        <select id="risk_building_density_level" name="building_density_level">
                                            <option value="">Select level</option>
                                            @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('building_density_level') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_road_accessibility">Road access</label>
                                        <select id="risk_road_accessibility" name="road_accessibility">
                                            <option value="">Select accessibility</option>
                                            @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'difficult' => 'Difficult', 'very_difficult' => 'Very Difficult'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('road_accessibility') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_assessment_level">Assessment</label>
                                        <select id="risk_assessment_level" name="assessment_level">
                                            <option value="">Select level</option>
                                            @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old('assessment_level') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_distance_to_hydrant">Hydrant dist. (m)</label>
                                        <input id="risk_distance_to_hydrant" name="distance_to_hydrant" type="number" step="0.01" min="0" max="50000" value="{{ old('distance_to_hydrant') }}">
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_distance_to_water_source">Water dist. (m)</label>
                                        <input id="risk_distance_to_water_source" name="distance_to_water_source" type="number" step="0.01" min="0" max="50000" value="{{ old('distance_to_water_source') }}">
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_estimated_affected_households">Households</label>
                                        <input id="risk_estimated_affected_households" name="estimated_affected_households" type="number" min="0" max="100000" value="{{ old('estimated_affected_households') }}">
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="risk_risk_score">Risk score</label>
                                        <input id="risk_risk_score" name="risk_score" type="number" step="0.01" min="0" max="100" value="{{ old('risk_score') }}">
                                    </div>
                                    <div class="risk-form-field full">
                                        <label for="risk_nearby_establishments">Nearby establishments</label>
                                        <textarea id="risk_nearby_establishments" name="nearby_establishments" placeholder="Schools, stores, gas outlets, terminals, markets">{{ old('nearby_establishments') }}</textarea>
                                    </div>
                                    <div class="risk-form-field full">
                                        <label for="risk_assessment_description">Assessment note</label>
                                        <textarea id="risk_assessment_description" name="assessment_description" placeholder="Why this area should be treated at the selected level">{{ old('assessment_description') }}</textarea>
                                    </div>
                                </div>
                            </section>

                            <section id="hazardModeSection" class="risk-form-card risk-mode-section">
                                <h4>Hazard details</h4>
                                <div class="risk-inline-hint"><i class="fa-regular fa-circle-info"></i><span>Use this for one concrete unsafe finding during patrol or inspection.</span></div>
                                <div class="risk-form-fields">
                                    <div class="risk-form-field">
                                        <label for="hazard_report_hazard_type">Hazard type</label>
                                        <select id="hazard_report_hazard_type" name="hazard_category">
                                            @foreach ($hazardTypeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('hazard_category', 'electrical_hazard') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="hazard_report_hazard_condition">Condition</label>
                                        <select id="hazard_report_hazard_condition">
                                            <option value="">Select hazard condition</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="risk-chip-grid">
                                    <label class="risk-chip"><input type="checkbox" name="near_hydrant" value="1" @checked(old('near_hydrant'))> Near hydrant</label>
                                    <label class="risk-chip"><input type="checkbox" name="near_water_source" value="1" @checked(old('near_water_source'))> Near water source</label>
                                    <label class="risk-chip"><input type="checkbox" name="blocked_hydrant" value="1" @checked(old('blocked_hydrant'))> Blocked hydrant</label>
                                    <label class="risk-chip"><input type="checkbox" name="informal_settlement" value="1" @checked(old('informal_settlement'))> Informal settlement</label>
                                    <label class="risk-chip"><input type="checkbox" name="cooking_fire_prone_activity" value="1" @checked(old('cooking_fire_prone_activity'))> Cooking / fire-prone activity</label>
                                    <label class="risk-chip"><input type="checkbox" name="combustible_materials_present" value="1" @checked(old('combustible_materials_present'))> Combustible materials present</label>
                                    <label class="risk-chip"><input type="checkbox" name="poor_waste_disposal" value="1" @checked(old('poor_waste_disposal'))> Poor waste disposal</label>
                                    <label class="risk-chip"><input type="checkbox" name="previous_fire_prone_location" value="1" @checked(old('previous_fire_prone_location'))> Previous fire-prone location</label>
                                </div>
                                <div class="risk-form-fields">
                                    <div class="risk-form-field full">
                                        <label for="hazard_report_remarks">Remarks</label>
                                        <textarea id="hazard_report_remarks" name="remarks" placeholder="Immediate action needed, exact unsafe condition, who observed it">{{ old('remarks') }}</textarea>
                                    </div>
                                </div>
                                <p class="risk-photo-caption">Image files are stored in `storage/app/public/risk-areas`. In the browser they are served from `/storage/risk-areas/...` when the storage link is active.</p>
                            </section>
                        </div>

                        <aside class="risk-form-stack">
                            <section class="risk-form-card">
                                <h4>Location capture</h4>
                                <div class="risk-inline-hint"><i class="fa-regular fa-circle-info"></i><span>Use current GPS or click the map to pin the exact point.</span></div>
                                <p>Use the officer’s current GPS position or click directly on the map to assign the exact point inside the barangay. Boundary validation can be improved later.</p>
                                <div class="hero-actions !mt-4">
                                    <button type="button" id="hazardsUseCurrentLocationBtn" class="hero-action-btn hero-action-btn--primary">
                                        <i class="fa-solid fa-location-crosshairs"></i>
                                        <span>Use My Location</span>
                                    </button>
                                </div>
                                <div id="hazardsRiskPickerMap" class="risk-map-picker mt-4"></div>
                                <div class="risk-form-fields">
                                    <div class="risk-form-field">
                                        <label for="hazards_modal_lat">Latitude</label>
                                        <input id="hazards_modal_lat" name="lat" type="text" value="{{ old('lat') }}" readonly>
                                    </div>
                                    <div class="risk-form-field">
                                        <label for="hazards_modal_lng">Longitude</label>
                                        <input id="hazards_modal_lng" name="lng" type="text" value="{{ old('lng') }}" readonly>
                                    </div>
                                </div>
                            </section>

                            <div class="risk-form-actions">
                                <div class="risk-form-note"><i class="fa-regular fa-circle-info"></i> Saved entries go to review and verification.</div>
                                <button type="submit" id="riskReportSubmitButton" class="risk-form-submit">Save Hazard / Risk Report</button>
                            </div>

                            <div class="risk-review-card">
                                <div class="tiny-label">Review before submit</div>
                                <p class="risk-form-note !mt-2">Check the chosen mode, the pinned point, and the photo evidence before saving.</p>
                                <div class="risk-review-card__grid">
                                    <div class="risk-review-card__item">
                                        <span>Mode</span>
                                        <strong>Hazard or risk only</strong>
                                    </div>
                                    <div class="risk-review-card__item">
                                        <span>Pin</span>
                                        <strong>Latitude and longitude are saved from the picker</strong>
                                    </div>
                                    <div class="risk-review-card__item">
                                        <span>Review</span>
                                        <strong>The record goes to barangay verification flow</strong>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('riskReportModal');
        const titleElement = document.getElementById('riskReportModalTitle');
        const copyElement = document.getElementById('riskReportModalCopy');
        const entryTypeInput = document.getElementById('riskReportEntryTypeInput');
        const reportKindInput = document.getElementById('riskReportKindInput');
        const conditionCodeInput = document.getElementById('riskReportConditionCodeInput');
        const reportMethodInput = document.getElementById('riskReportMethodInput');
        const reportFormModeInput = document.getElementById('riskReportFormMode');
        const reportEditingIdInput = document.getElementById('riskReportEditingId');
        const submitButton = document.getElementById('riskReportSubmitButton');
        const riskModeSection = document.getElementById('riskModeSection');
        const hazardModeSection = document.getElementById('hazardModeSection');
        const riskTypeField = document.getElementById('hazard_report_risk_type');
        const hazardTypeField = document.getElementById('hazard_report_hazard_type');
        const riskConditionField = document.getElementById('hazard_report_risk_condition');
        const hazardConditionField = document.getElementById('hazard_report_hazard_condition');
        const formElement = modal.querySelector('form');
        const latInput = document.getElementById('hazards_modal_lat');
        const lngInput = document.getElementById('hazards_modal_lng');
        const currentLocationButton = document.getElementById('hazardsUseCurrentLocationBtn');
        const pickerElement = document.getElementById('hazardsRiskPickerMap');
        const hallPayload = @json($hazardsPageHallPayload);
        const riskAreaRecords = @json($riskAreaCrudPayload);
        const riskConditionOptions = @json($riskConditionOptions);
        const hazardConditionOptions = @json($hazardConditionOptions);
        const tableTypeFilter = document.getElementById('riskTableTypeFilter');
        const tableStatusFilter = document.getElementById('riskTableStatusFilter');
        const tableSearchInput = document.getElementById('riskTableSearchInput');
        const tableBody = document.getElementById('riskTableBody');
        let tableRows = tableBody ? Array.from(tableBody.querySelectorAll('tr[data-entry-type]')) : [];
        const noResultsRow = document.getElementById('riskTableNoResults');
        const modalOpenButtons = document.querySelectorAll('[data-risk-report-open]');
        const modalCloseButtons = document.querySelectorAll('[data-risk-report-close]');
        const modeButtons = document.querySelectorAll('[data-risk-mode]');
        const editButtons = document.querySelectorAll('[data-risk-edit]');
        const initialMode = '{{ old('report_kind', 'risk') }}';
        const initialFormMode = '{{ old('form_mode', 'create') }}';

        if (!modal || !titleElement || !copyElement || !reportKindInput || !pickerElement || typeof L === 'undefined') {
            return;
        }

        const defaultLat = hallPayload && hallPayload.lat !== null && hallPayload.lat !== undefined
            ? Number(hallPayload.lat)
            : 15.8222;
        const defaultLng = hallPayload && hallPayload.lng !== null && hallPayload.lng !== undefined
            ? Number(hallPayload.lng)
            : 120.3297;

        let pickerMap = null;
        let pickerMarker = null;

        const fieldMap = {
            title: document.getElementById('hazard_report_title'),
            location_description: document.getElementById('hazard_report_location_description'),
            street_or_sitio: document.getElementById('hazard_report_street'),
            station_id: document.getElementById('hazard_report_station'),
            entry_type: entryTypeInput,
            risk_type: riskTypeField,
            hazard_category: hazardTypeField,
            condition_code: conditionCodeInput,
            notes: document.getElementById('hazard_report_notes'),
            population_density_level: document.getElementById('risk_population_density_level'),
            building_density_level: document.getElementById('risk_building_density_level'),
            road_accessibility: document.getElementById('risk_road_accessibility'),
            assessment_level: document.getElementById('risk_assessment_level'),
            distance_to_hydrant: document.getElementById('risk_distance_to_hydrant'),
            distance_to_water_source: document.getElementById('risk_distance_to_water_source'),
            estimated_affected_households: document.getElementById('risk_estimated_affected_households'),
            risk_score: document.getElementById('risk_risk_score'),
            nearby_establishments: document.getElementById('risk_nearby_establishments'),
            assessment_description: document.getElementById('risk_assessment_description'),
            remarks: document.getElementById('hazard_report_remarks'),
        };

        const checkboxKeys = [
            'near_hydrant',
            'near_water_source',
            'blocked_hydrant',
            'informal_settlement',
            'cooking_fire_prone_activity',
            'combustible_materials_present',
            'poor_waste_disposal',
            'previous_fire_prone_location',
        ];

        function openModal() {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('menu-open');
            window.setTimeout(() => pickerMap?.invalidateSize(), 120);
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('menu-open');
        }

        function applyTableFilters() {
            if (!tableRows.length) {
                if (noResultsRow) {
                    noResultsRow.style.display = '';
                }
                return;
            }

            const typeFilter = (tableTypeFilter?.value || 'all').toLowerCase();
            const statusFilter = tableStatusFilter?.value || 'all';
            const keyword = (tableSearchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            tableRows.forEach((row) => {
                const rowType = (row.dataset.entryType || '').toLowerCase();
                const rowStatus = (row.dataset.status || '').toUpperCase();
                const rowSearch = (row.dataset.search || '').toLowerCase();
                const matchesType = typeFilter === 'all' || rowType === typeFilter;
                const matchesStatus = statusFilter === 'all' || rowStatus === String(statusFilter).toUpperCase();
                const matchesKeyword = keyword === '' || rowSearch.includes(keyword);
                const isVisible = matchesType && matchesStatus && matchesKeyword;

                row.style.display = isVisible ? '' : 'none';

                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (noResultsRow) {
                noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function buildPhotoGridHtml(photos) {
            if (!Array.isArray(photos) || photos.length === 0) {
                return '';
            }

            const items = photos.slice(0, 3).map((photo) => `
                <a href="${escapeHtml(photo.url)}" target="_blank" rel="noopener" class="risk-photo-thumb">
                    <img src="${escapeHtml(photo.url)}" alt="${escapeHtml(photo.name || 'Risk area photo')}">
                </a>
            `).join('');

            return `<div class="risk-photo-grid" style="grid-template-columns: repeat(3, minmax(0, 72px)); max-width: 240px;">${items}</div>`;
        }

        function bindEditButton(button) {
            button.addEventListener('click', function () {
                const record = JSON.parse(button.dataset.riskEdit || '{}');
                initPickerMap();
                setEditMode(record);
                openModal();
            });
        }

        function bindDeleteForm(form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (!window.confirm('Send this record for deletion review?')) {
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalLabel = submitBtn?.querySelector('span')?.textContent || 'Delete';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    const span = submitBtn.querySelector('span');
                    if (span) {
                        span.textContent = 'Sending...';
                    }
                }

                try {
                    const formData = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || !payload.success) {
                        const message = payload.message
                            || Object.values(payload.errors || {}).flat()[0]
                            || 'Unable to send delete request.';
                        throw new Error(message);
                    }

                    const row = form.closest('tr[data-entry-type]');
                    if (row) {
                        row.dataset.search = String(row.dataset.search || '').replace(/delete\s*\/\s*pending/ig, '').trim() + ' DELETE / PENDING';
                        const reviewCell = row.children[4];
                        if (reviewCell) {
                            reviewCell.textContent = payload.data?.review_label || 'DELETE / PENDING';
                        }

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            const span = submitBtn.querySelector('span');
                            if (span) {
                                span.textContent = 'Delete Pending';
                            }
                        }
                    }

                    tableRows = Array.from(tableBody.querySelectorAll('tr[data-entry-type]'));
                    applyTableFilters();
                } catch (error) {
                    window.alert(error.message || 'Unable to send delete request.');

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        const span = submitBtn.querySelector('span');
                        if (span) {
                            span.textContent = originalLabel;
                        }
                    }
                }
            });
        }

        function prependRiskTableRow(record) {
            if (!tableBody || !record) {
                return;
            }

            const row = document.createElement('tr');
            row.setAttribute('data-risk-row-id', String(record.id));
            row.dataset.entryType = record.entry_type || 'risk';
            row.dataset.status = record.status_label || 'PENDING';
            row.dataset.search = record.search || '';

            const editPayload = escapeHtml(JSON.stringify(record.edit_payload || {}));
            const deleteDisabled = record.delete_pending ? 'disabled' : '';
            const deleteLabel = record.delete_pending ? 'Delete Pending' : 'Delete';

            row.innerHTML = `
                <td>${escapeHtml(record.date || '')}</td>
                <td>${escapeHtml(record.title || 'Untitled entry')}</td>
                <td>${escapeHtml(record.type_label || '')}</td>
                <td>${escapeHtml(record.status_label || 'PENDING')}</td>
                <td>${escapeHtml(record.review_label || 'CREATE / PENDING')}</td>
                <td>
                    <div>${escapeHtml(record.location_label || 'General barangay area')}</div>
                    ${buildPhotoGridHtml(record.photos || [])}
                </td>
                <td>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm"
                            data-risk-edit="${editPayload}"
                        >
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Edit</span>
                        </button>
                        <form method="POST" action="{{ url('/barangay/risk-map') }}/${record.id}" onsubmit="return confirm('Send this record for deletion review?');">
                            <input type="hidden" name="_token" value="${escapeHtml(formElement.querySelector('input[name=\"_token\"]')?.value || '')}">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="return_to" value="hazards">
                            <button type="submit" class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm !bg-white !text-red-700 !border !border-red-200 !shadow-none" ${deleteDisabled}>
                                <i class="fa-solid fa-trash-can"></i>
                                <span>${deleteLabel}</span>
                            </button>
                        </form>
                    </div>
                </td>
            `;

            tableBody.insertBefore(row, tableBody.firstChild);
            const editButton = row.querySelector('[data-risk-edit]');
            if (editButton) {
                bindEditButton(editButton);
            }
            const deleteForm = row.querySelector('form');
            if (deleteForm) {
                bindDeleteForm(deleteForm);
            }

            tableRows = Array.from(tableBody.querySelectorAll('tr[data-entry-type]'));
            applyTableFilters();
        }

        function updateRiskTableRow(record) {
            if (!tableBody || !record) {
                return;
            }

            const row = tableBody.querySelector(`tr[data-risk-row-id="${record.id}"]`);

            if (!row) {
                prependRiskTableRow(record);
                return;
            }

            row.dataset.entryType = record.entry_type || 'risk';
            row.dataset.status = record.status_label || 'PENDING';
            row.dataset.search = record.search || '';
            row.setAttribute('data-risk-row-id', String(record.id));

            row.children[0].textContent = record.date || '';
            row.children[1].textContent = record.title || 'Untitled entry';
            row.children[2].textContent = record.type_label || '';
            row.children[3].textContent = record.status_label || 'PENDING';
            row.children[4].textContent = record.review_label || 'UPDATE / PENDING';
            row.children[5].innerHTML = `
                <div>${escapeHtml(record.location_label || 'General barangay area')}</div>
                ${buildPhotoGridHtml(record.photos || [])}
            `;

            const editPayload = escapeHtml(JSON.stringify(record.edit_payload || {}));
            const deleteDisabled = record.delete_pending ? 'disabled' : '';
            const deleteLabel = record.delete_pending ? 'Delete Pending' : 'Delete';

            row.children[6].innerHTML = `
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm"
                        data-risk-edit="${editPayload}"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span>Edit</span>
                    </button>
                    <form method="POST" action="{{ url('/barangay/risk-map') }}/${record.id}">
                        <input type="hidden" name="_token" value="${escapeHtml(formElement.querySelector('input[name=\"_token\"]')?.value || '')}">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="return_to" value="hazards">
                        <button type="submit" class="hero-action-btn !min-h-[36px] !px-3 !py-0 !text-sm !bg-white !text-red-700 !border !border-red-200 !shadow-none" ${deleteDisabled}>
                            <i class="fa-solid fa-trash-can"></i>
                            <span>${deleteLabel}</span>
                        </button>
                    </form>
                </div>
            `;

            const editButton = row.querySelector('[data-risk-edit]');
            if (editButton) {
                bindEditButton(editButton);
            }
            const deleteForm = row.querySelector('form');
            if (deleteForm) {
                bindDeleteForm(deleteForm);
            }

            tableRows = Array.from(tableBody.querySelectorAll('tr[data-entry-type]'));
            applyTableFilters();
        }

        function fillConditionSelect(selectElement, options, selectedValue, placeholder) {
            if (!selectElement) {
                return;
            }

            const normalizedOptions = options || {};
            selectElement.innerHTML = '';

            const firstOption = document.createElement('option');
            firstOption.value = '';
            firstOption.textContent = placeholder;
            selectElement.appendChild(firstOption);

            Object.entries(normalizedOptions).forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = value === selectedValue;
                selectElement.appendChild(option);
            });
        }

        function syncConditionOptions() {
            const activeMode = reportKindInput.value === 'hazard' ? 'hazard' : 'risk';

            if (activeMode === 'risk') {
                const selectedRiskType = riskTypeField.value || 'overcrowded_area';
                const options = riskConditionOptions[selectedRiskType] || {};
                const selected = options[conditionCodeInput.value] ? conditionCodeInput.value : '';
                fillConditionSelect(riskConditionField, options, selected, 'Select risk condition');

                if (!selected && riskConditionField.options.length > 1) {
                    riskConditionField.selectedIndex = 1;
                }

                conditionCodeInput.value = riskConditionField.value || '';
                fillConditionSelect(hazardConditionField, {}, '', 'Select hazard condition');
                hazardTypeField.value = '';
            } else {
                const selectedHazardType = hazardTypeField.value || 'electrical_hazard';
                const options = hazardConditionOptions[selectedHazardType] || {};
                const selected = options[conditionCodeInput.value] ? conditionCodeInput.value : '';
                fillConditionSelect(hazardConditionField, options, selected, 'Select hazard condition');

                if (!selected && hazardConditionField.options.length > 1) {
                    hazardConditionField.selectedIndex = 1;
                }

                conditionCodeInput.value = hazardConditionField.value || '';
                fillConditionSelect(riskConditionField, {}, '', 'Select risk condition');
                riskTypeField.value = riskTypeField.value || 'overcrowded_area';
            }
        }

        function resetFormForCreate(mode = 'risk') {
            formElement.reset();
            formElement.action = '{{ route('barangay.risk-map.store') }}';
            reportMethodInput.value = 'POST';
            reportFormModeInput.value = 'create';
            reportEditingIdInput.value = '';
            submitButton.textContent = 'Save Hazard / Risk Report';
            setCoordinates(defaultLat, defaultLng);
            applyMode(mode);
        }

        function applyMode(mode) {
            const normalizedMode = mode === 'hazard' ? 'hazard' : 'risk';
            entryTypeInput.value = normalizedMode;
            reportKindInput.value = normalizedMode;

            modeButtons.forEach((button) => {
                button.classList.toggle('is-active', button.dataset.riskMode === normalizedMode);
            });

            const isRiskMode = normalizedMode === 'risk';
            riskModeSection.classList.toggle('is-active', isRiskMode);
            hazardModeSection.classList.toggle('is-active', !isRiskMode);

            if (isRiskMode) {
                titleElement.textContent = 'Report Risk';
                copyElement.textContent = 'Document a broader area-level fire exposure that should be reviewed, verified, and mapped for barangay planning.';
                if (!riskTypeField.value) {
                    riskTypeField.value = 'overcrowded_area';
                }
                hazardTypeField.value = '';
            } else {
                titleElement.textContent = 'Report Hazard';
                copyElement.textContent = 'Document a specific unsafe condition such as blocked hydrants, exposed wiring, combustible buildup, or other patrol-observed fire hazards.';
                if (!hazardTypeField.value) {
                    hazardTypeField.value = 'electrical_hazard';
                }
                riskTypeField.value = '';
            }

            syncConditionOptions();
        }

        function setEditMode(record) {
            if (!record || !record.id) {
                return;
            }

            formElement.action = `{{ url('/barangay/risk-map') }}/${record.id}`;
            reportMethodInput.value = 'PUT';
            reportFormModeInput.value = 'edit';
            reportEditingIdInput.value = String(record.id);
            submitButton.textContent = 'Update Hazard / Risk Report';

            Object.entries(fieldMap).forEach(([key, element]) => {
                if (!element) {
                    return;
                }

                const value = record[key];
                element.value = value === null || value === undefined ? '' : value;
            });

            checkboxKeys.forEach((key) => {
                const checkbox = formElement.querySelector(`input[name="${key}"]`);
                if (checkbox) {
                    checkbox.checked = Boolean(record[key]);
                }
            });

            if (record.lat !== null && record.lng !== null) {
                setCoordinates(Number(record.lat), Number(record.lng));
                pickerMap?.setView([Number(record.lat), Number(record.lng)], 16, { animate: true });
            }

            applyMode(record.report_kind || record.entry_type || 'hazard');
        }

        function setCoordinates(lat, lng) {
            const fixedLat = Number(lat).toFixed(7);
            const fixedLng = Number(lng).toFixed(7);
            latInput.value = fixedLat;
            lngInput.value = fixedLng;

            if (!pickerMarker) {
                pickerMarker = L.marker([lat, lng]).addTo(pickerMap);
            } else {
                pickerMarker.setLatLng([lat, lng]);
            }
        }

        function initPickerMap() {
            if (pickerMap) {
                return;
            }

            pickerMap = L.map(pickerElement).setView([defaultLat, defaultLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(pickerMap);

            setCoordinates(latInput.value || defaultLat, lngInput.value || defaultLng);

            pickerMap.on('click', function (event) {
                setCoordinates(event.latlng.lat, event.latlng.lng);
            });

            if (hallPayload && hallPayload.lat !== null && hallPayload.lng !== null) {
                L.circleMarker([Number(hallPayload.lat), Number(hallPayload.lng)], {
                    radius: 8,
                    color: '#0f766e',
                    weight: 3,
                    fillColor: '#10b981',
                    fillOpacity: 0.9,
                }).addTo(pickerMap).bindPopup(hallPayload.title || 'Barangay Hall');
            }
        }

        modalOpenButtons.forEach((button) => {
            button.addEventListener('click', function () {
                initPickerMap();
                resetFormForCreate(button.dataset.riskReportOpen || 'risk');
                openModal();
            });
        });

        editButtons.forEach(bindEditButton);
        tableBody?.querySelectorAll('form[action*="/barangay/risk-map/"]').forEach(bindDeleteForm);

        modalCloseButtons.forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        modeButtons.forEach((button) => {
            button.addEventListener('click', function () {
                applyMode(button.dataset.riskMode || 'risk');
            });
        });

        riskTypeField?.addEventListener('change', function () {
            if (reportKindInput.value === 'risk') {
                syncConditionOptions();
            }
        });

        hazardTypeField?.addEventListener('change', function () {
            if (reportKindInput.value === 'hazard') {
                syncConditionOptions();
            }
        });

        riskConditionField?.addEventListener('change', function () {
            if (reportKindInput.value === 'risk') {
                conditionCodeInput.value = riskConditionField.value || '';
            }
        });

        hazardConditionField?.addEventListener('change', function () {
            if (reportKindInput.value === 'hazard') {
                conditionCodeInput.value = hazardConditionField.value || '';
            }
        });

        tableTypeFilter?.addEventListener('change', applyTableFilters);
        tableStatusFilter?.addEventListener('change', applyTableFilters);
        tableSearchInput?.addEventListener('input', applyTableFilters);

        formElement?.addEventListener('submit', async function (event) {
            event.preventDefault();

            submitButton.disabled = true;
            const originalLabel = submitButton.textContent;
            submitButton.textContent = 'Saving...';

            try {
                const formData = new FormData(formElement);
                const response = await fetch(formElement.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.success) {
                    const message = payload.message
                        || Object.values(payload.errors || {}).flat()[0]
                        || 'Unable to save the hazard or risk report.';
                    throw new Error(message);
                }

                if (reportFormModeInput.value === 'edit') {
                    updateRiskTableRow(payload.data);
                } else {
                    prependRiskTableRow(payload.data);
                }
                resetFormForCreate(reportKindInput.value || 'risk');
                closeModal();
            } catch (error) {
                window.alert(error.message || 'Unable to save the hazard or risk report.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = originalLabel;
            }
        });

        currentLocationButton?.addEventListener('click', function () {
            if (!navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                setCoordinates(lat, lng);
                pickerMap?.setView([lat, lng], 16, { animate: true });
            });
        });

        initPickerMap();
        applyMode(initialMode);
        applyTableFilters();

        if (modal.classList.contains('is-open')) {
            if (initialFormMode === 'edit') {
                const editRecord = riskAreaRecords.find((record) => String(record.id) === String(reportEditingIdInput.value || ''));
                if (editRecord) {
                    setEditMode(editRecord);
                }
            } else {
                resetFormForCreate(initialMode);
            }
            openModal();
        }
    });
</script>
@endpush
