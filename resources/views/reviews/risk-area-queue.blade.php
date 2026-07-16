<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense · {{ $pageTitle }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css', 'resources/css/admin/announcements.css', 'resources/css/admin/resource-management.css'])

    <style>
        body {
            background: #f8fafc;
        }

        .review-shell {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            min-height: 100vh;
        }

        .review-main {
            min-width: 0;
        }

        .review-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 28px 8px;
        }

        .review-topbar h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f172a;
        }

        .review-topbar p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        .review-page {
            padding: 20px 28px 40px;
        }

        .review-alert {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(245, 158, 11, 0.22);
            background: #fff7ed;
            color: #9a3412;
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 18px 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .summary-card__label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary-card__value {
            margin-top: 14px;
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
        }

        .filters-card,
        .queue-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.06);
        }

        .filters-card {
            padding: 20px;
            margin-bottom: 22px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1.4fr repeat(3, minmax(0, 0.7fr)) auto;
            gap: 14px;
            align-items: end;
        }

        .filters-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filters-group label {
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .filters-group input,
        .filters-group select,
        .review-notes {
            width: 100%;
            border: 1px solid #dbe4ee;
            border-radius: 14px;
            padding: 11px 14px;
            font-size: 0.95rem;
            color: #0f172a;
            background: #fff;
        }

        .filters-group input:focus,
        .filters-group select:focus,
        .review-notes:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
        }

        .filter-submit {
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            padding: 11px 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .queue-card {
            padding: 24px;
        }

        .queue-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .queue-head h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .queue-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.92rem;
        }

        .queue-count {
            padding: 10px 14px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-weight: 700;
        }

        .request-list {
            display: grid;
            gap: 14px;
        }

        .request-tile {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: center;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 18px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #fffdfb 100%);
        }

        .request-tile__title {
            margin: 0;
            font-size: 1.02rem;
            font-weight: 800;
            color: #0f172a;
        }

        .request-tile__sub {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        .request-tile__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .request-tile__trailing {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .chip--pending { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }
        .chip--approved { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .chip--rejected { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
        .chip--neutral { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
        .chip--risk { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .chip--hazard { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

        .request-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .request-field {
            padding: 14px;
            border-radius: 16px;
            background: #f8fafc;
            border: 1px solid #edf2f7;
        }

        .request-field__label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
        }

        .request-field__value {
            margin-top: 8px;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.5;
            word-break: break-word;
        }

        .request-copy {
            margin: 0;
            color: #475569;
            line-height: 1.6;
            white-space: pre-line;
        }

        .photo-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .photo-strip img {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .review-form__row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto;
            gap: 12px;
            align-items: end;
        }

        .review-actions {
            display: contents;
        }

        .action-btn {
            border: none;
            border-radius: 14px;
            min-height: 46px;
            padding: 0 16px;
            font-weight: 700;
            cursor: pointer;
        }

        .action-btn--approve {
            background: #16a34a;
            color: #fff;
        }

        .action-btn--reject {
            background: #e2e8f0;
            color: #0f172a;
        }

        .review-history {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .empty-state {
            padding: 48px 18px;
            text-align: center;
            color: #64748b;
        }

        .pagination-wrap {
            margin-top: 20px;
        }

        .review-trigger {
            border: none;
            min-height: 46px;
            padding: 0 16px;
            border-radius: 14px;
            background: #0f172a;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
        }

        .review-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.56);
            z-index: 5000;
        }

        .review-modal.is-open {
            display: flex;
        }

        .review-modal__panel {
            width: min(920px, 100%);
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: #fff;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
            padding: 24px;
        }

        .review-modal__head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .review-modal__title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
        }

        .review-modal__copy {
            margin: 6px 0 0;
            color: #64748b;
            line-height: 1.6;
        }

        .review-modal__close {
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            cursor: pointer;
        }

        .manage-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 18px;
            margin-top: 18px;
        }

        .manage-form-stack {
            display: grid;
            gap: 16px;
        }

        .manage-form-card {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .manage-form-card h4 {
            margin: 0 0 8px;
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .manage-form-card p {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .manage-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .manage-field-grid .full {
            grid-column: 1 / -1;
        }

        .manage-toggle {
            display: inline-flex;
            gap: 10px;
            margin-bottom: 14px;
        }

        .manage-toggle__btn {
            border: 1px solid #dbe4ee;
            background: #fff;
            color: #475569;
            min-height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }

        .manage-toggle__btn.is-active {
            border-color: #fb923c;
            background: #fff7ed;
            color: #c2410c;
        }

        .manage-chip-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .manage-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 14px;
            border: 1px solid #dbe4ee;
            background: #fff;
            color: #334155;
            font-size: 0.9rem;
        }

        .manage-chip input {
            width: 16px;
            height: 16px;
        }

        .manage-inline-note {
            margin-top: 10px;
            color: #64748b;
            font-size: 0.82rem;
        }

        .mobile-menu-btn {
            display: none;
            border: none;
            background: #fff;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            color: #334155;
        }

        @media (max-width: 1100px) {
            .summary-grid,
            .request-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filters-grid,
            .review-form__row {
                grid-template-columns: 1fr 1fr;
            }

            .review-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 768px) {
            .review-shell {
                grid-template-columns: 1fr;
            }

            .review-topbar,
            .review-page {
                padding-left: 16px;
                padding-right: 16px;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .summary-grid,
            .filters-grid,
            .request-grid,
            .review-form__row,
            .manage-form-grid,
            .manage-field-grid,
            .manage-chip-grid {
                grid-template-columns: 1fr;
            }

            .queue-head,
            .review-modal__head,
            .request-tile {
                flex-direction: column;
                align-items: flex-start;
            }

            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .request-tile {
                grid-template-columns: 1fr;
            }

            .request-tile__trailing {
                width: 100%;
                justify-content: stretch;
            }

            .review-trigger {
                width: 100%;
            }
        }
    </style>
</head>
<body class="chief-dashboard-page">
    @include($sidebarPartial, ['active' => $activeNav])
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <main class="main-content review-main">
        <div class="review-topbar">
            <div class="flex items-start gap-4">
                <button id="openSidebar" class="mobile-menu-btn" type="button" aria-label="Open menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h1>{{ $pageTitle }}</h1>
                    <p>{{ $pageSubtitle }}</p>
                </div>
            </div>
            <div class="chip chip--neutral">
                <i class="fa-regular fa-calendar"></i>
                <span>{{ now()->format('l, F j, Y') }}</span>
            </div>
        </div>

        <div class="review-page chief-theme-shell">
            @if (session('status'))
                <div class="review-alert">{{ session('status') }}</div>
            @endif

            <section class="chief-theme-hero">
                <div>
                    <span class="chief-theme-hero__eyebrow">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Operations Review Queue
                    </span>
                    <h2 class="chief-theme-hero__title">{{ $pageTitle }}</h2>
                    <p class="chief-theme-hero__copy">{{ $pageSubtitle }} Review barangay-submitted hazard and risk requests before they become part of station operations.</p>
                </div>
                <div class="chief-theme-summary-grid">
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Total Requests</div>
                        <div class="chief-theme-summary-value">{{ $summaryCounts['all'] }}</div>
                    </article>
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Pending</div>
                        <div class="chief-theme-summary-value">{{ $summaryCounts['pending'] }}</div>
                    </article>
                    <article class="chief-theme-summary-card">
                        <div class="chief-theme-summary-label">Approved</div>
                        <div class="chief-theme-summary-value">{{ $summaryCounts['approved'] }}</div>
                    </article>
                </div>
            </section>

            <section class="summary-grid">
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-inbox"></i> Total Requests</div>
                    <div class="summary-card__value">{{ $summaryCounts['all'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-regular fa-clock"></i> Pending</div>
                    <div class="summary-card__value">{{ $summaryCounts['pending'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-circle-check"></i> Approved</div>
                    <div class="summary-card__value">{{ $summaryCounts['approved'] }}</div>
                </article>
                <article class="summary-card">
                    <div class="summary-card__label"><i class="fa-solid fa-ban"></i> Rejected</div>
                    <div class="summary-card__value">{{ $summaryCounts['rejected'] }}</div>
                </article>
            </section>

            <section class="filters-card">
                <form method="GET" action="{{ route($indexRouteName) }}" class="filters-grid">
                    <div class="filters-group">
                        <label for="search">Search</label>
                        <input id="search" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Search title, barangay, station, sitio, or type">
                    </div>
                    <div class="filters-group">
                        <label for="review_status">Review Status</label>
                        <select id="review_status" name="review_status">
                            <option value="all" @selected($filters['review_status'] === 'all')>All statuses</option>
                            <option value="pending" @selected($filters['review_status'] === 'pending')>Pending</option>
                            <option value="approved" @selected($filters['review_status'] === 'approved')>Approved</option>
                            <option value="rejected" @selected($filters['review_status'] === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    <div class="filters-group">
                        <label for="entry_type">Entry Type</label>
                        <select id="entry_type" name="entry_type">
                            <option value="all" @selected($filters['entry_type'] === 'all')>All entries</option>
                            <option value="hazard" @selected($filters['entry_type'] === 'hazard')>Hazard only</option>
                            <option value="risk" @selected($filters['entry_type'] === 'risk')>Risk only</option>
                        </select>
                    </div>
                    <div class="filters-group">
                        <label for="review_type">Request Type</label>
                        <select id="review_type" name="review_type">
                            <option value="all" @selected($filters['review_type'] === 'all')>All request types</option>
                            <option value="create" @selected($filters['review_type'] === 'create')>Create</option>
                            <option value="update" @selected($filters['review_type'] === 'update')>Update</option>
                            <option value="delete" @selected($filters['review_type'] === 'delete')>Delete</option>
                        </select>
                    </div>
                    <button type="submit" class="filter-submit">Apply Filters</button>
                </form>
            </section>

            <section class="queue-card">
                <div class="queue-head">
                    <div>
                        <h2>Review Request List</h2>
                        <p>Use this queue to inspect barangay-submitted hazard and risk entries before they are finalized in operations.</p>
                    </div>
                    <div class="queue-count">{{ $reviewQueue->total() }} item{{ $reviewQueue->total() === 1 ? '' : 's' }}</div>
                </div>

                @if ($reviewQueue->isEmpty())
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open text-3xl mb-3"></i>
                        <p>No hazard or risk requests matched the current filters.</p>
                    </div>
                @else
                    <div class="request-list">
                        @foreach ($reviewQueue as $item)
                            @php
                                $entryType = strtolower($item->entry_type ?? 'risk');
                                $reviewStatus = strtolower($item->review_status ?? 'pending');
                                $reviewType = strtolower($item->review_type ?? 'create');
                                $barangayName = $item->barangayHall?->barangay?->barangay_name ?? 'Unassigned barangay';
                                $cityName = $item->barangayHall?->barangay?->city?->city_name ?? null;
                                $typeLabel = $entryType === 'hazard'
                                    ? ($item->hazard_category ? ucwords(str_replace('_', ' ', $item->hazard_category)) : 'Hazard')
                                    : ($item->risk_type ? ucwords(str_replace('_', ' ', $item->risk_type)) : 'Risk');
                                $typeIcon = $entryType === 'hazard'
                                    ? ($hazardTypeIcons[$item->hazard_category ?? ''] ?? 'fa-triangle-exclamation')
                                    : ($riskTypeIcons[$item->risk_type ?? ''] ?? 'fa-layer-group');
                                $finalEntryType = in_array($entryType, ['hazard', 'risk'], true) ? $entryType : 'risk';
                                $finalRiskType = array_key_exists($item->risk_type ?? '', $riskTypeOptions)
                                    ? $item->risk_type
                                    : array_key_first($riskTypeOptions);
                                $finalHazardCategory = array_key_exists($item->hazard_category ?? '', $hazardTypeOptions)
                                    ? $item->hazard_category
                                    : array_key_first($hazardTypeOptions);
                                $finalConditionCode = $item->condition_code ?? $item->mode;
                            @endphp
                            <article class="request-tile">
                                <div>
                                    <h3 class="request-tile__title">{{ $item->marker?->title ?? 'Untitled request' }}</h3>
                                    <p class="request-tile__sub">
                                        {{ $barangayName }}@if($cityName), {{ $cityName }}@endif
                                        · {{ $item->street_or_sitio ?: 'General barangay area' }}
                                        · {{ $item->station?->station_name ?: 'No station assigned' }}
                                    </p>
                                    <div class="request-tile__meta">
                                            <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}">
                                                <i class="fa-solid {{ $typeIcon }}"></i>
                                                {{ strtoupper($entryType) }}
                                            </span>
                                            <span class="chip chip--neutral">{{ strtoupper($reviewType) }} REQUEST</span>
                                            <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                            <span class="chip chip--neutral">{{ $typeLabel }}</span>
                                            @if ($item->assessment_level)
                                                <span class="chip chip--neutral">ASSESSMENT · {{ strtoupper($item->assessment_level) }}</span>
                                            @endif
                                    </div>
                                </div>
                                <div class="request-tile__trailing">
                                    <button
                                        type="button"
                                        class="review-trigger"
                                        data-review-open="review-modal-{{ $item->risk_area_id }}"
                                    >
                                        Review
                                    </button>
                                </div>
                            </article>

                            <div class="review-modal" id="review-modal-{{ $item->risk_area_id }}" aria-hidden="true">
                                <div class="review-modal__panel">
                                    <div class="review-modal__head">
                                        <div>
                                            <h3 class="review-modal__title"><i class="fa-solid {{ $typeIcon }}" style="margin-right:10px;"></i>{{ $item->marker?->title ?? 'Untitled request' }}</h3>
                                            <p class="review-modal__copy">Review the full hazard or risk submission details, then approve or reject this request.</p>
                                        </div>
                                        <button type="button" class="review-modal__close" data-review-close>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <div class="request-tile__meta">
                                        <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}">{{ strtoupper($entryType) }}</span>
                                        <span class="chip chip--neutral">{{ strtoupper($reviewType) }} REQUEST</span>
                                        <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                        <span class="chip chip--neutral">{{ $typeLabel }}</span>
                                    </div>

                                    <div class="request-grid" style="margin-top: 18px;">
                                        <div class="request-field">
                                            <div class="request-field__label">Barangay</div>
                                            <div class="request-field__value">{{ $barangayName }}@if($cityName), {{ $cityName }}@endif</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Street / Sitio</div>
                                            <div class="request-field__value">{{ $item->street_or_sitio ?: 'General barangay area' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Coordinates</div>
                                            <div class="request-field__value">
                                                {{ $item->marker?->lat !== null ? number_format((float) $item->marker?->lat, 6) : 'N/A' }},
                                                {{ $item->marker?->lng !== null ? number_format((float) $item->marker?->lng, 6) : 'N/A' }}
                                            </div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Reported By</div>
                                            <div class="request-field__value">{{ $item->reporter?->first_name ? trim($item->reporter->first_name . ' ' . $item->reporter->last_name) : 'Unknown user' }}</div>
                                        </div>
                                    </div>

                                    <div class="request-field" style="margin-top: 18px;">
                                        <div class="request-field__label">Description</div>
                                        <div class="request-field__value">
                                            <p class="request-copy">{{ $item->marker?->description ?: 'No description provided.' }}</p>
                                        </div>
                                    </div>

                                    <div class="request-grid" style="margin-top: 18px;">
                                        <div class="request-field">
                                            <div class="request-field__label">Condition</div>
                                            <div class="request-field__value">{{ $item->condition_label ?: 'Not specified' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Current Status</div>
                                            <div class="request-field__value">{{ strtoupper($item->status ?? 'pending') }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Risk Score</div>
                                            <div class="request-field__value">{{ $item->risk_score !== null ? number_format((float) $item->risk_score, 2) : 'Not scored' }}</div>
                                        </div>
                                        <div class="request-field">
                                            <div class="request-field__label">Affected Households</div>
                                            <div class="request-field__value">{{ $item->estimated_affected_households ?? 'Not provided' }}</div>
                                        </div>
                                    </div>

                                    @if ($item->photos->isNotEmpty())
                                        <div class="request-field" style="margin-top: 18px;">
                                            <div class="request-field__label">Attached Photos</div>
                                            <div class="photo-strip">
                                                @foreach ($item->photos as $photo)
                                                    <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener">
                                                        <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}">
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($item->reviewer || $item->review_notes)
                                        <div class="review-history">
                                            <strong>Latest Review</strong><br>
                                            Status: {{ strtoupper($reviewStatus) }}
                                            @if ($item->reviewer)
                                                · Reviewed by {{ trim(($item->reviewer->first_name ?? '') . ' ' . ($item->reviewer->last_name ?? '')) }}
                                            @endif
                                            @if ($item->reviewed_at)
                                                · {{ $item->reviewed_at->format('M d, Y h:i A') }}
                                            @endif
                                            @if ($item->review_notes)
                                                <br>Notes: {{ $item->review_notes }}
                                            @endif
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route($reviewRouteName, $item->risk_area_id) }}" style="margin-top: 20px;" data-final-classification-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="entry_type" value="{{ $filters['entry_type'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">

                                        @if ($reviewType !== 'delete')
                                            <input type="hidden" name="final_entry_type" value="{{ $finalEntryType }}" data-entry-type-input>
                                            <input type="hidden" name="final_condition_code" value="{{ $finalConditionCode }}" data-condition-code-input>

                                            <section class="manage-form-card" style="margin-bottom: 16px;">
                                                <h4>Final classification</h4>
                                                <p>Before approval, confirm whether this submission should become a live hazard or risk marker.</p>
                                                <div class="manage-toggle">
                                                    <button type="button" class="manage-toggle__btn {{ $finalEntryType === 'hazard' ? 'is-active' : '' }}" data-manage-mode="hazard">Hazard</button>
                                                    <button type="button" class="manage-toggle__btn {{ $finalEntryType === 'risk' ? 'is-active' : '' }}" data-manage-mode="risk">Risk</button>
                                                </div>

                                                <div class="manage-field-grid" data-mode-section="risk" style="{{ $finalEntryType === 'risk' ? '' : 'display:none;' }}">
                                                    <div class="filters-group">
                                                        <label for="review_risk_type_{{ $item->risk_area_id }}">Risk type</label>
                                                        <select id="review_risk_type_{{ $item->risk_area_id }}" name="final_risk_type" data-risk-type-select>
                                                            <option value="">Select risk type</option>
                                                            @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                                <optgroup label="{{ $groupLabel }}">
                                                                    @foreach ($groupValues as $value)
                                                                        <option value="{{ $value }}" @selected($finalRiskType === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="filters-group">
                                                        <label for="review_risk_condition_{{ $item->risk_area_id }}">Risk condition</label>
                                                        <select id="review_risk_condition_{{ $item->risk_area_id }}" data-risk-condition-select data-current-value="{{ $finalConditionCode }}">
                                                            <option value="">Select risk condition</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="manage-field-grid" data-mode-section="hazard" style="{{ $finalEntryType === 'hazard' ? '' : 'display:none;' }}">
                                                    <div class="filters-group">
                                                        <label for="review_hazard_type_{{ $item->risk_area_id }}">Hazard type</label>
                                                        <select id="review_hazard_type_{{ $item->risk_area_id }}" name="final_hazard_category" data-hazard-type-select>
                                                            <option value="">Select hazard type</option>
                                                            @foreach ($hazardTypeOptions as $value => $label)
                                                                <option value="{{ $value }}" @selected($finalHazardCategory === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="filters-group">
                                                        <label for="review_hazard_condition_{{ $item->risk_area_id }}">Hazard condition</label>
                                                        <select id="review_hazard_condition_{{ $item->risk_area_id }}" data-hazard-condition-select data-current-value="{{ $finalConditionCode }}">
                                                            <option value="">Select hazard condition</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </section>
                                        @endif

                                        <div class="review-form__row">
                                            <div class="filters-group">
                                                <label for="review_notes_{{ $item->risk_area_id }}">Review Notes</label>
                                                <textarea
                                                    id="review_notes_{{ $item->risk_area_id }}"
                                                    name="review_notes"
                                                    class="review-notes"
                                                    rows="3"
                                                    placeholder="Add review remarks, validation notes, or next-step instructions."
                                                ></textarea>
                                            </div>

                                            <div class="review-actions">
                                                <button type="submit" name="decision" value="approved" class="action-btn action-btn--approve">
                                                    <i class="fa-solid fa-circle-check mr-2"></i>Approve
                                                </button>
                                                <button type="submit" name="decision" value="rejected" class="action-btn action-btn--reject">
                                                    <i class="fa-solid fa-ban mr-2"></i>Reject
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="pagination-wrap">
                        {{ $reviewQueue->links() }}
                    </div>
                @endif
            </section>

            <section class="queue-card" style="margin-top: 22px;">
                <div class="queue-head">
                    <div>
                        <h2>Active Hazards & Risks</h2>
                        <p>These are already live records. Chief and admin can still adjust visibility, update details, or remove them from the system here.</p>
                    </div>
                    <div class="queue-count">{{ $activeEntries->count() }} active</div>
                </div>

                @if ($activeEntries->isEmpty())
                    <div class="empty-state">
                        <i class="fa-solid fa-shield-heart text-3xl mb-3"></i>
                        <p>No active hazard or risk entries matched the current filters.</p>
                    </div>
                @else
                    <div class="request-list">
                        @foreach ($activeEntries as $item)
                            @php
                                $entryType = strtolower($item->entry_type ?? 'risk');
                                $reviewStatus = strtolower($item->review_status ?? 'approved');
                                $barangayName = $item->barangayHall?->barangay?->barangay_name ?? 'Unassigned barangay';
                                $cityName = $item->barangayHall?->barangay?->city?->city_name ?? null;
                                $typeLabel = $entryType === 'hazard'
                                    ? ($item->hazard_category ? ucwords(str_replace('_', ' ', $item->hazard_category)) : 'Hazard')
                                    : ($item->risk_type ? ucwords(str_replace('_', ' ', $item->risk_type)) : 'Risk');
                                $visibility = strtolower($item->marker?->status ?? 'active');
                                $typeIcon = $entryType === 'hazard'
                                    ? ($hazardTypeIcons[$item->hazard_category ?? ''] ?? 'fa-triangle-exclamation')
                                    : ($riskTypeIcons[$item->risk_type ?? ''] ?? 'fa-layer-group');
                            @endphp
                            <article class="request-tile">
                                <div>
                                    <h3 class="request-tile__title">{{ $item->marker?->title ?? 'Untitled active entry' }}</h3>
                                    <p class="request-tile__sub">
                                        {{ $barangayName }}@if($cityName), {{ $cityName }}@endif
                                        · {{ $item->street_or_sitio ?: 'General barangay area' }}
                                        · {{ $item->station?->station_name ?: 'No station assigned' }}
                                    </p>
                                    <div class="request-tile__meta">
                                        <span class="chip {{ $entryType === 'hazard' ? 'chip--hazard' : 'chip--risk' }}"><i class="fa-solid {{ $typeIcon }}"></i>{{ strtoupper($entryType) }}</span>
                                        <span class="chip chip--neutral"><i class="fa-solid {{ $typeIcon }}"></i>{{ $typeLabel }}</span>
                                        <span class="chip chip--neutral">VISIBILITY · {{ strtoupper($visibility) }}</span>
                                        <span class="chip chip--{{ $reviewStatus }}">{{ strtoupper($reviewStatus) }}</span>
                                        @if ($item->assessment_level)
                                            <span class="chip chip--neutral">ASSESSMENT · {{ strtoupper($item->assessment_level) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="request-tile__trailing">
                                    <button
                                        type="button"
                                        class="review-trigger"
                                        data-review-open="manage-modal-{{ $item->risk_area_id }}"
                                    >
                                        Manage
                                    </button>
                                </div>
                            </article>

                            <div class="review-modal" id="manage-modal-{{ $item->risk_area_id }}" aria-hidden="true">
                                <div class="review-modal__panel">
                                    <div class="review-modal__head">
                                        <div>
                                            <h3 class="review-modal__title"><i class="fa-solid {{ $typeIcon }}" style="margin-right:10px;"></i>{{ $item->marker?->title ?? 'Untitled active entry' }}</h3>
                                            <p class="review-modal__copy">Update live visibility and record details here, or remove the active hazard/risk if it should no longer remain on the operational map.</p>
                                        </div>
                                        <button type="button" class="review-modal__close" data-review-close>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route($activeUpdateRouteName, $item->risk_area_id) }}" enctype="multipart/form-data" data-manage-form>
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">

                                        <input type="hidden" name="entry_type" value="{{ $entryType }}" data-entry-type-input>
                                        <input type="hidden" name="condition_code" value="{{ $item->condition_code ?? $item->mode }}" data-condition-code-input>

                                        <div class="manage-form-grid">
                                            <div class="manage-form-stack">
                                                <section class="manage-form-card">
                                                    <h4>Core details</h4>
                                                    <p>Use the same structure as the barangay hazard/risk form so live records stay consistent with submitted entries.</p>
                                                    <div class="manage-toggle">
                                                        <button type="button" class="manage-toggle__btn {{ $entryType === 'hazard' ? 'is-active' : '' }}" data-manage-mode="hazard">Hazard</button>
                                                        <button type="button" class="manage-toggle__btn {{ $entryType === 'risk' ? 'is-active' : '' }}" data-manage-mode="risk">Risk</button>
                                                    </div>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group full">
                                                            <label for="active_title_{{ $item->risk_area_id }}">Title</label>
                                                            <input id="active_title_{{ $item->risk_area_id }}" type="text" name="title" value="{{ $item->marker?->title }}">
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_description_{{ $item->risk_area_id }}">Location description</label>
                                                            <textarea id="active_description_{{ $item->risk_area_id }}" name="location_description" class="review-notes" rows="4">{{ $item->marker?->description }}</textarea>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_street_{{ $item->risk_area_id }}">Street / Purok / Sitio</label>
                                                            <input id="active_street_{{ $item->risk_area_id }}" type="text" name="street_or_sitio" value="{{ $item->street_or_sitio }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_station_{{ $item->risk_area_id }}">Linked station</label>
                                                            <select id="active_station_{{ $item->risk_area_id }}" name="station_id">
                                                                <option value="">No linked station</option>
                                                                @foreach ($stations as $station)
                                                                    <option value="{{ $station->station_id }}" @selected((string) $item->station_id === (string) $station->station_id)>{{ $station->station_name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_status_{{ $item->risk_area_id }}">Record status</label>
                                                            <select id="active_status_{{ $item->risk_area_id }}" name="status">
                                                                <option value="pending" @selected(($item->status ?? '') === 'pending')>Pending</option>
                                                                <option value="verified" @selected(($item->status ?? '') === 'verified')>Verified</option>
                                                                <option value="resolved" @selected(($item->status ?? '') === 'resolved')>Resolved</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_visibility_{{ $item->risk_area_id }}">Visibility</label>
                                                            <select id="active_visibility_{{ $item->risk_area_id }}" name="visibility">
                                                                <option value="active" @selected(($item->marker?->status ?? 'active') === 'active')>Visible / Active</option>
                                                                <option value="inactive" @selected(($item->marker?->status ?? '') === 'inactive')>Hidden / Inactive</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_notes_{{ $item->risk_area_id }}">Notes</label>
                                                            <textarea id="active_notes_{{ $item->risk_area_id }}" name="notes" class="review-notes" rows="3">{{ $item->remarks }}</textarea>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_photos_{{ $item->risk_area_id }}">Add photo evidence</label>
                                                            <input id="active_photos_{{ $item->risk_area_id }}" type="file" name="photos[]" multiple accept=".jpg,.jpeg,.png,.webp">
                                                            <div class="manage-inline-note">New photos are appended to the existing evidence gallery.</div>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="manage-form-card" data-mode-section="risk" style="{{ $entryType === 'risk' ? '' : 'display:none;' }}">
                                                    <h4>Area-level risk assessment</h4>
                                                    <p>These fields match the barangay risk-entry workflow for area-level fire exposure.</p>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group">
                                                            <label for="active_risk_type_{{ $item->risk_area_id }}">Risk type</label>
                                                            <select id="active_risk_type_{{ $item->risk_area_id }}" name="risk_type" data-risk-type-select>
                                                                <option value="">Select risk type</option>
                                                                @foreach ($riskTypeGroups as $groupLabel => $groupValues)
                                                                    <optgroup label="{{ $groupLabel }}">
                                                                        @foreach ($groupValues as $value)
                                                                            <option value="{{ $value }}" @selected(($item->risk_type ?? '') === $value)>{{ $riskTypeOptions[$value] ?? $value }}</option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_risk_condition_{{ $item->risk_area_id }}">Condition</label>
                                                            <select id="active_risk_condition_{{ $item->risk_area_id }}" data-risk-condition-select data-current-value="{{ $item->condition_code ?? $item->mode }}">
                                                                <option value="">Select risk condition</option>
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_population_density_{{ $item->risk_area_id }}">Population density level</label>
                                                            <select id="active_population_density_{{ $item->risk_area_id }}" name="population_density_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->population_density_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_building_density_{{ $item->risk_area_id }}">Building density level</label>
                                                            <select id="active_building_density_{{ $item->risk_area_id }}" name="building_density_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->building_density_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_road_access_{{ $item->risk_area_id }}">Road accessibility</label>
                                                            <select id="active_road_access_{{ $item->risk_area_id }}" name="road_accessibility">
                                                                <option value="">Select accessibility</option>
                                                                @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'difficult' => 'Difficult', 'very_difficult' => 'Very Difficult'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->road_accessibility ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_assessment_{{ $item->risk_area_id }}">Assessment level</label>
                                                            <select id="active_assessment_{{ $item->risk_area_id }}" name="assessment_level">
                                                                <option value="">Select level</option>
                                                                @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->assessment_level ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_hydrant_distance_{{ $item->risk_area_id }}">Distance to hydrant (m)</label>
                                                            <input id="active_hydrant_distance_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="50000" name="distance_to_hydrant" value="{{ $item->distance_to_hydrant }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_water_distance_{{ $item->risk_area_id }}">Distance to water source (m)</label>
                                                            <input id="active_water_distance_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="50000" name="distance_to_water_source" value="{{ $item->distance_to_water_source }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_households_{{ $item->risk_area_id }}">Estimated affected households</label>
                                                            <input id="active_households_{{ $item->risk_area_id }}" type="number" min="0" max="100000" name="estimated_affected_households" value="{{ $item->estimated_affected_households }}">
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_risk_score_{{ $item->risk_area_id }}">Risk score</label>
                                                            <input id="active_risk_score_{{ $item->risk_area_id }}" type="number" step="0.01" min="0" max="100" name="risk_score" value="{{ $item->risk_score }}">
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_nearby_{{ $item->risk_area_id }}">Nearby establishments</label>
                                                            <textarea id="active_nearby_{{ $item->risk_area_id }}" name="nearby_establishments" class="review-notes" rows="3">{{ $item->nearby_establishments }}</textarea>
                                                        </div>
                                                        <div class="filters-group full">
                                                            <label for="active_assessment_description_{{ $item->risk_area_id }}">Assessment description</label>
                                                            <textarea id="active_assessment_description_{{ $item->risk_area_id }}" name="assessment_description" class="review-notes" rows="3">{{ $item->assessment_description }}</textarea>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section class="manage-form-card" data-mode-section="hazard" style="{{ $entryType === 'hazard' ? '' : 'display:none;' }}">
                                                    <h4>Hazard condition details</h4>
                                                    <p>These fields mirror the barangay hazard-report flow for observed unsafe conditions.</p>
                                                    <div class="manage-field-grid">
                                                        <div class="filters-group">
                                                            <label for="active_hazard_type_{{ $item->risk_area_id }}">Hazard type</label>
                                                            <select id="active_hazard_type_{{ $item->risk_area_id }}" name="hazard_category" data-hazard-type-select>
                                                                @foreach ($hazardTypeOptions as $value => $label)
                                                                    <option value="{{ $value }}" @selected(($item->hazard_category ?? '') === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="filters-group">
                                                            <label for="active_hazard_condition_{{ $item->risk_area_id }}">Condition</label>
                                                            <select id="active_hazard_condition_{{ $item->risk_area_id }}" data-hazard-condition-select data-current-value="{{ $item->condition_code ?? $item->mode }}">
                                                                <option value="">Select hazard condition</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="manage-chip-grid" style="margin-top: 14px;">
                                                        <label class="manage-chip"><input type="checkbox" name="near_hydrant" value="1" @checked($item->near_hydrant)> Near hydrant</label>
                                                        <label class="manage-chip"><input type="checkbox" name="near_water_source" value="1" @checked($item->near_water_source)> Near water source</label>
                                                        <label class="manage-chip"><input type="checkbox" name="blocked_hydrant" value="1" @checked($item->blocked_hydrant)> Blocked hydrant</label>
                                                        <label class="manage-chip"><input type="checkbox" name="informal_settlement" value="1" @checked($item->informal_settlement)> Informal settlement</label>
                                                        <label class="manage-chip"><input type="checkbox" name="cooking_fire_prone_activity" value="1" @checked($item->cooking_fire_prone_activity)> Cooking / fire-prone activity</label>
                                                        <label class="manage-chip"><input type="checkbox" name="combustible_materials_present" value="1" @checked($item->combustible_materials_present)> Combustible materials present</label>
                                                        <label class="manage-chip"><input type="checkbox" name="poor_waste_disposal" value="1" @checked($item->poor_waste_disposal)> Poor waste disposal</label>
                                                        <label class="manage-chip"><input type="checkbox" name="previous_fire_prone_location" value="1" @checked($item->previous_fire_prone_location)> Previous fire-prone location</label>
                                                    </div>
                                                    <div class="filters-group" style="margin-top: 14px;">
                                                        <label for="active_remarks_{{ $item->risk_area_id }}">Remarks</label>
                                                        <textarea id="active_remarks_{{ $item->risk_area_id }}" name="remarks" class="review-notes" rows="3">{{ $item->remarks }}</textarea>
                                                    </div>
                                                </section>
                                            </div>

                                            <aside class="manage-form-stack">
                                                <section class="manage-form-card">
                                                    <h4>Location and status</h4>
                                                    <p>These fields stay visible while you manage the live entry.</p>
                                                    <div class="request-grid">
                                                        <div class="request-field">
                                                            <div class="request-field__label">Barangay</div>
                                                            <div class="request-field__value">{{ $barangayName }}@if($cityName), {{ $cityName }}@endif</div>
                                                        </div>
                                                        <div class="request-field">
                                                            <div class="request-field__label">Coordinates</div>
                                                            <div class="request-field__value">
                                                                {{ $item->marker?->lat !== null ? number_format((float) $item->marker?->lat, 6) : 'N/A' }},
                                                                {{ $item->marker?->lng !== null ? number_format((float) $item->marker?->lng, 6) : 'N/A' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </section>

                                                @if ($item->photos->isNotEmpty())
                                                    <section class="manage-form-card">
                                                        <h4>Attached photos</h4>
                                                        <p>Existing image evidence attached to this live record.</p>
                                                        <div class="photo-strip">
                                                            @foreach ($item->photos as $photo)
                                                                <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener">
                                                                    <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}">
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </section>
                                                @endif

                                                <section class="manage-form-card">
                                                    <h4>Save behavior</h4>
                                                    <p>The live record remains active, but all edited fields are updated using the same data structure as the barangay creation form.</p>
                                                    <div class="review-actions">
                                                        <button type="submit" class="action-btn action-btn--approve">
                                                            <i class="fa-solid fa-floppy-disk mr-2"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </section>
                                            </aside>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route($activeDeleteRouteName, $item->risk_area_id) }}" style="margin-top: 14px;" onsubmit="return confirm('Remove this active hazard or risk entry?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="review_status" value="{{ $filters['review_status'] }}">
                                        <input type="hidden" name="entry_type" value="{{ $filters['entry_type'] }}">
                                        <input type="hidden" name="review_type" value="{{ $filters['review_type'] }}">
                                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                                        <input type="hidden" name="page" value="{{ $reviewQueue->currentPage() }}">
                                        <button type="submit" class="action-btn action-btn--reject">
                                            <i class="fa-solid fa-trash-can mr-2"></i>Remove Entry
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            const openSidebar = document.getElementById('openSidebar');
            const reviewOpenButtons = document.querySelectorAll('[data-review-open]');
            const reviewCloseButtons = document.querySelectorAll('[data-review-close]');
            const riskConditionOptions = @json($riskConditionOptions);
            const hazardConditionOptions = @json($hazardConditionOptions);

            if (sidebar && sidebarBackdrop && openSidebar) {
                openSidebar.addEventListener('click', () => {
                    sidebar.classList.add('open', 'active');
                    sidebarBackdrop.classList.remove('hidden');
                    document.body.classList.add('menu-open');
                });

                sidebarBackdrop.addEventListener('click', () => {
                    sidebar.classList.remove('open', 'active');
                    sidebarBackdrop.classList.add('hidden');
                    document.body.classList.remove('menu-open');
                });
            }

            const closeModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            const openModal = (modal) => {
                if (!modal) {
                    return;
                }

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            reviewOpenButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const modal = document.getElementById(button.dataset.reviewOpen);
                    openModal(modal);
                });
            });

            reviewCloseButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(button.closest('.review-modal'));
                });
            });

            document.querySelectorAll('.review-modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.review-modal.is-open').forEach((modal) => {
                    closeModal(modal);
                });
            });

            const populateConditionSelect = (select, options, selectedValue) => {
                if (!select) {
                    return;
                }

                const currentPlaceholder = select.querySelector('option')?.textContent || 'Select condition';
                select.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = currentPlaceholder;
                select.appendChild(placeholder);

                Object.entries(options || {}).forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = value === selectedValue;
                    select.appendChild(option);
                });
            };

            document.querySelectorAll('[data-manage-form], [data-final-classification-form]').forEach((form) => {
                const entryTypeInput = form.querySelector('[data-entry-type-input]');
                const conditionCodeInput = form.querySelector('[data-condition-code-input]');
                const modeButtons = form.querySelectorAll('[data-manage-mode]');
                const riskTypeSelect = form.querySelector('[data-risk-type-select]');
                const hazardTypeSelect = form.querySelector('[data-hazard-type-select]');
                const riskConditionSelect = form.querySelector('[data-risk-condition-select]');
                const hazardConditionSelect = form.querySelector('[data-hazard-condition-select]');
                const riskSection = form.querySelector('[data-mode-section="risk"]');
                const hazardSection = form.querySelector('[data-mode-section="hazard"]');

                const setMode = (mode) => {
                    if (entryTypeInput) {
                        entryTypeInput.value = mode;
                    }

                    modeButtons.forEach((button) => {
                        button.classList.toggle('is-active', button.dataset.manageMode === mode);
                    });

                    if (riskSection) {
                        riskSection.style.display = mode === 'risk' ? '' : 'none';
                    }

                    if (hazardSection) {
                        hazardSection.style.display = mode === 'hazard' ? '' : 'none';
                    }

                    if (mode === 'risk') {
                        const options = riskConditionOptions[riskTypeSelect?.value || ''] || {};
                        const selected = riskConditionSelect?.dataset.currentValue || conditionCodeInput?.value || '';
                        populateConditionSelect(riskConditionSelect, options, selected);
                        if (conditionCodeInput && riskConditionSelect) {
                            conditionCodeInput.value = riskConditionSelect.value || '';
                        }
                    } else {
                        const options = hazardConditionOptions[hazardTypeSelect?.value || ''] || {};
                        const selected = hazardConditionSelect?.dataset.currentValue || conditionCodeInput?.value || '';
                        populateConditionSelect(hazardConditionSelect, options, selected);
                        if (conditionCodeInput && hazardConditionSelect) {
                            conditionCodeInput.value = hazardConditionSelect.value || '';
                        }
                    }
                };

                modeButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        if (riskConditionSelect) {
                            riskConditionSelect.dataset.currentValue = '';
                        }
                        if (hazardConditionSelect) {
                            hazardConditionSelect.dataset.currentValue = '';
                        }
                        setMode(button.dataset.manageMode || 'risk');
                    });
                });

                riskTypeSelect?.addEventListener('change', () => {
                    populateConditionSelect(riskConditionSelect, riskConditionOptions[riskTypeSelect.value || ''] || {}, '');
                    if ((entryTypeInput?.value || 'risk') === 'risk' && conditionCodeInput && riskConditionSelect) {
                        conditionCodeInput.value = riskConditionSelect.value || '';
                    }
                });

                hazardTypeSelect?.addEventListener('change', () => {
                    populateConditionSelect(hazardConditionSelect, hazardConditionOptions[hazardTypeSelect.value || ''] || {}, '');
                    if ((entryTypeInput?.value || 'risk') === 'hazard' && conditionCodeInput && hazardConditionSelect) {
                        conditionCodeInput.value = hazardConditionSelect.value || '';
                    }
                });

                riskConditionSelect?.addEventListener('change', () => {
                    if ((entryTypeInput?.value || 'risk') === 'risk' && conditionCodeInput) {
                        conditionCodeInput.value = riskConditionSelect.value || '';
                    }
                });

                hazardConditionSelect?.addEventListener('change', () => {
                    if ((entryTypeInput?.value || 'risk') === 'hazard' && conditionCodeInput) {
                        conditionCodeInput.value = hazardConditionSelect.value || '';
                    }
                });

                setMode(entryTypeInput?.value || 'risk');
            });
        });
    </script>
</body>
</html>
