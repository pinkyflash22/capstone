<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - My Station</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/firefighters/portal.css'])

    @php
        $stationImagePath = $station?->profileMedia?->path
            ?? $station?->marker?->multimedia?->path
            ?? $station?->marker?->image;
        $stationImageUrl = null;
        $currentOfficerName = trim(($stationOfficer?->user?->first_name ?? '') . ' ' . ($stationOfficer?->user?->last_name ?? '')) ?: 'Current Station Officer';

        if ($stationImagePath) {
            $normalizedStationImage = str_replace('\\', '/', $stationImagePath);
            $stationImageUrl = str_starts_with($normalizedStationImage, 'http://') || str_starts_with($normalizedStationImage, 'https://')
                ? $normalizedStationImage
                : asset('storage/' . ltrim($normalizedStationImage, '/'));
        }

        $stationAddress = collect([
            $station?->address?->street,
            $station?->address?->barangay?->barangay_name,
            $station?->address?->barangay?->city?->city_name,
        ])->filter()->join(', ');

        $stationGallery = collect($station?->galleryMedia ?? [])
            ->map(function ($media) {
                $normalizedPath = str_replace('\\', '/', (string) $media->path);

                return [
                    'id' => $media->station_media_id,
                    'caption' => $media->caption,
                    'url' => str_starts_with($normalizedPath, 'http://') || str_starts_with($normalizedPath, 'https://')
                        ? $normalizedPath
                        : asset('storage/' . ltrim($normalizedPath, '/')),
                ];
            })
            ->values();

        $spotReportStore = $spotReports->mapWithKeys(function ($report) use ($currentOfficerName) {
            return [
                $report->spot_investigation_report_id => [
                    'id' => $report->spot_investigation_report_id,
                    'type' => 'spot',
                    'incident_id' => $report->incident_id,
                    'incident_location' => $report->incident?->marker?->title,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: $currentOfficerName,
                    'fire_started' => optional($report->fire_started)->toIso8601String(),
                    'fire_out' => optional($report->fire_out)->toIso8601String(),
                    'estimated_damage' => $report->estimated_damage,
                    'occupancy' => $report->occupancy,
                    'estimated_area' => $report->estimated_area,
                    'dead' => $report->dead,
                    'injured' => $report->injured,
                    'owner' => $report->owner,
                    'report_completed_at' => optional($report->report_completed_at)->toIso8601String(),
                    'memorandum_for' => $report->memorandum_for,
                    'place_of_occurrence' => $report->place_of_occurrence,
                    'involved_structure' => $report->involved_structure,
                    'establishment_name' => $report->establishment_name,
                    'occupant' => $report->occupant,
                    'highest_alarm_level' => $report->highest_alarm_level,
                    'affected_establishments_count' => $report->affected_establishments_count,
                    'description' => $report->description,
                    'fatality_details' => $report->fatality_details,
                    'details_of_investigation' => $report->details_of_investigation,
                    'disposition' => $report->disposition,
                    'noted_by_name' => trim(($report->notedBy?->user?->first_name ?? '') . ' ' . ($report->notedBy?->user?->last_name ?? '')),
                    'approved_by_name' => trim(($report->approvedBy?->user?->first_name ?? '') . ' ' . ($report->approvedBy?->user?->last_name ?? '')),
                    'noted_by_station_officer_id' => $report->noted_by_station_officer_id,
                    'approved_by_station_officer_id' => $report->approved_by_station_officer_id,
                    'has_progress' => (bool) $report->progressInvestigation,
                ],
            ];
        })->all();

        $progressReportStore = $progressReports->mapWithKeys(function ($report) use ($currentOfficerName) {
            return [
                $report->progress_investigation_report_id => [
                    'id' => $report->progress_investigation_report_id,
                    'type' => 'progress',
                    'spot_investigation_id' => $report->spot_investigation_id,
                    'incident_location' => $report->spotInvestigation?->incident?->marker?->title,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: $currentOfficerName,
                    'fire_origin_id' => $report->fire_origin_id,
                    'fire_origin_name' => $report->fireOrigin?->origin,
                    'fire_cause' => $report->fire_cause,
                    'responsible_arson' => $report->responsible_arson,
                    'has_final' => (bool) $report->finalInvestigation,
                ],
            ];
        })->all();

        $finalReportStore = $finalReports->mapWithKeys(function ($report) {
            return [
                $report->final_investigation_report_id => [
                    'id' => $report->final_investigation_report_id,
                    'type' => 'final',
                    'progress_investigation_id' => $report->progress_investigation_id,
                    'incident_location' => $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title,
                    'exact_address' => $report->exact_address,
                    'total_damage' => $report->total_damage,
                    'created_at' => optional($report->created_at)->toIso8601String(),
                    'fire_origin_name' => $report->progressInvestigation?->fireOrigin?->origin,
                    'fire_cause' => $report->progressInvestigation?->fire_cause,
                ],
            ];
        })->all();
    @endphp

    <style>
        .station-shell { display: grid; gap: 24px; }
        .station-hero {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr);
            gap: 24px;
            padding: 28px;
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(255, 205, 86, 0.22), transparent 34%),
                linear-gradient(135deg, #fff4ea 0%, #fff8f4 45%, #ffffff 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }
        .station-hero-media {
            min-height: 260px;
            border-radius: 24px;
            overflow: hidden;
            background: linear-gradient(135deg, #ffe8d6 0%, #fff3db 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c63d26;
        }
        .station-hero-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .station-hero-media i {
            font-size: 4rem;
        }
        .station-hero-content {
            display: grid;
            gap: 18px;
            align-content: start;
        }
        .station-chip {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
            color: #9a3412;
            background: #fff7f1;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }
        .station-title {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            color: #172033;
        }
        .station-subtitle {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.65;
            max-width: 760px;
        }
        .station-hero-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .hero-detail {
            padding: 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .hero-detail-label {
            margin-bottom: 6px;
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .hero-detail-value {
            color: #172033;
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.5;
        }

        .station-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            gap: 24px;
        }
        .station-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }
        .station-gallery-item {
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.9);
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
        }
        .station-gallery-item img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: block;
        }
        .station-gallery-caption {
            padding: 12px 14px;
            color: #475569;
            font-size: 0.84rem;
            line-height: 1.5;
        }
        .section-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            padding: 22px 24px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.86);
        }
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.08rem;
            font-weight: 800;
            color: #172033;
        }
        .section-title i { color: #c63d26; }
        .section-subtitle {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }
        .section-body { padding: 22px 24px 24px; }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        .stat-block {
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .stat-block-label {
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .stat-block-value {
            margin-top: 10px;
            color: #172033;
            font-size: 1.85rem;
            font-weight: 800;
        }

        .crew-list {
            display: grid;
            gap: 12px;
        }
        .crew-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .crew-avatar {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            border-radius: 16px;
            background: linear-gradient(135deg, #ffe8d6 0%, #fff3db 100%);
            border: 1px solid rgba(198, 61, 38, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #c63d26;
            font-size: 0.98rem;
            font-weight: 800;
        }
        .crew-name {
            font-size: 0.96rem;
            font-weight: 800;
            color: #172033;
        }
        .crew-role {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.83rem;
        }

        .table-wrap { overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            padding: 12px 14px;
            text-align: left;
            font-size: 0.74rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
            white-space: nowrap;
        }
        .data-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(241, 245, 249, 0.95);
            color: #334155;
            font-size: 0.9rem;
            vertical-align: top;
        }
        .data-table tbody tr:hover { background: #fffaf4; }
        .table-title {
            color: #172033;
            font-weight: 700;
        }
        .table-muted {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 0.78rem;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 800;
        }
        .status-available { background: #dcfce7; color: #15803d; }
        .status-busy { background: #fef3c7; color: #b45309; }
        .status-dispatched { background: #dbeafe; color: #1d4ed8; }
        .status-default { background: #e5e7eb; color: #475569; }

        .equipment-grid {
            display: grid;
            gap: 14px;
        }
        .equipment-item {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto auto auto;
            gap: 12px;
            align-items: center;
            padding: 16px 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .equipment-name {
            color: #172033;
            font-size: 0.95rem;
            font-weight: 800;
        }
        .equipment-category {
            margin-top: 4px;
            color: #64748b;
            font-size: 0.82rem;
        }
        .qty-pill {
            min-width: 92px;
            padding: 8px 10px;
            text-align: center;
            border-radius: 14px;
            font-size: 0.78rem;
            font-weight: 800;
        }
        .qty-available { background: #dcfce7; color: #15803d; }
        .qty-maintenance { background: #ffedd5; color: #c2410c; }
        .qty-damaged { background: #fee2e2; color: #b91c1c; }

        .report-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 0 24px 20px;
        }
        .report-tab {
            border: 0;
            cursor: pointer;
            padding: 10px 16px;
            border-radius: 999px;
            background: #fff7f3;
            color: #9a3412;
            font-size: 0.82rem;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .report-tab.active {
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }
        .report-panel {
            display: none;
            padding: 0 24px 24px;
        }
        .report-panel.active { display: block; }
        .action-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 18px;
            border: 0;
            border-radius: 14px;
            font-size: 0.86rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(198, 61, 38, 0.2);
        }
        .btn-outline {
            color: #9a3412;
            background: #fff7f3;
            border: 1px solid rgba(198, 61, 38, 0.14);
        }
        .btn-danger {
            color: #fff;
            background: linear-gradient(135deg, #dc2626 0%, #f97316 100%);
        }
        .btn-ghost {
            color: #475569;
            background: #f8fafc;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .table-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .table-action {
            border: 0;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .table-action.view {
            color: #1d4ed8;
            background: #dbeafe;
        }
        .table-action.edit {
            color: #9a3412;
            background: #ffedd5;
        }
        .table-action.delete {
            color: #b91c1c;
            background: #fee2e2;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .detail-item {
            padding: 14px;
            border-radius: 16px;
            background: linear-gradient(135deg, #fffaf6 0%, #fff 100%);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }
        .detail-label {
            color: #94a3b8;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .detail-value {
            margin-top: 6px;
            color: #172033;
            font-size: 0.92rem;
            font-weight: 700;
            line-height: 1.5;
            white-space: pre-wrap;
        }
        .toast-stack {
            position: fixed;
            top: 24px;
            right: 24px;
            display: grid;
            gap: 12px;
            z-index: 3200;
        }
        .report-toast {
            min-width: 280px;
            max-width: 360px;
            padding: 14px 16px;
            border-radius: 16px;
            color: #fff;
            font-size: 0.88rem;
            font-weight: 700;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.18);
            animation: toastIn 0.22s ease;
        }
        .report-toast.info { background: linear-gradient(135deg, #475569 0%, #1e293b 100%); }
        .report-toast.success { background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); }
        .report-toast.error { background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%); }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .report-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.55);
            z-index: 2600;
        }
        .report-modal.active { display: flex; }
        .report-modal-panel {
            width: min(760px, 100%);
            max-height: calc(100vh - 40px);
            overflow: hidden;
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 24px 56px rgba(15, 23, 42, 0.2);
        }
        .report-modal-header,
        .report-modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 20px 22px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.9);
        }
        .report-modal-footer {
            justify-content: flex-end;
            border-top: 1px solid rgba(226, 232, 240, 0.9);
            border-bottom: 0;
        }
        .report-modal-body {
            display: grid;
            gap: 16px;
            max-height: calc(100vh - 210px);
            padding: 22px;
            overflow-y: auto;
        }
        .report-modal-title {
            color: #172033;
            font-size: 1.08rem;
            font-weight: 800;
        }
        .report-close {
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 999px;
            background: #fff7f3;
            color: #9a3412;
            cursor: pointer;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .form-group {
            display: grid;
            gap: 7px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-label {
            color: #475569;
            font-size: 0.82rem;
            font-weight: 700;
        }
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #dbe1ea;
            border-radius: 14px;
            background: #fff;
            color: #172033;
            font-size: 0.92rem;
            outline: none;
        }
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: rgba(198, 61, 38, 0.36);
            box-shadow: 0 0 0 4px rgba(198, 61, 38, 0.08);
        }
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .empty-box {
            padding: 36px 18px;
            text-align: center;
            color: #94a3b8;
        }
        .empty-box i {
            margin-bottom: 12px;
            font-size: 2.6rem;
            color: #cbd5e1;
        }

        @media (max-width: 1180px) {
            .station-grid { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 900px) {
            .station-hero { grid-template-columns: 1fr; }
            .station-hero-grid,
            .form-grid,
            .detail-grid,
            .stat-grid { grid-template-columns: 1fr; }
            .equipment-item { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .equipment-item { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'station'])

    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'My Station',
            'subtitle' => 'Station profile, officers, crew assignments, equipment inventory, and investigation reports'
        ])

        <div class="station-shell">
            <section class="station-hero">
                <div class="station-hero-media">
                    @if($stationImageUrl)
                        <img src="{{ $stationImageUrl }}" alt="{{ $station?->station_name }}">
                    @else
                        <i class="fa-solid fa-building-shield"></i>
                    @endif
                </div>

                <div class="station-hero-content">
                    <span class="station-chip"><i class="fa-solid fa-fire"></i> BFP Station Details</span>
                    <div>
                        <h1 class="station-title">{{ $station?->station_name ?? 'Station not assigned' }}</h1>
                        <p class="station-subtitle">
                            This page is scoped to your assigned station only. It shows the current station roster, your active crew composition, station-linked equipment assignments, and the spot, progress, and final investigation reports filed under this station.
                        </p>
                    </div>

                    <div class="station-hero-grid">
                        <div class="hero-detail">
                            <div class="hero-detail-label">Station Code</div>
                            <div class="hero-detail-value">{{ $station?->station_code ?? 'N/A' }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Contact Number</div>
                            <div class="hero-detail-value">{{ $station?->contact_number ?? 'No contact number on file' }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Email Address</div>
                            <div class="hero-detail-value">{{ $station?->email ?? 'No station email on file' }}</div>
                        </div>
                        <div class="hero-detail">
                            <div class="hero-detail-label">Station Address</div>
                            <div class="hero-detail-value">{{ $stationAddress ?: 'No station address on file' }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-images"></i> Station Photos</div>
                        <div class="section-subtitle">Profile and gallery images uploaded by your station command or system administrator.</div>
                    </div>
                </div>
                <div class="section-body">
                    @if($stationGallery->isEmpty())
                        <div class="empty-box">
                            <i class="fa-solid fa-image"></i>
                            <div>No additional station photos have been uploaded yet.</div>
                        </div>
                    @else
                        <div class="station-gallery-grid">
                            @foreach($stationGallery as $photo)
                                <div class="station-gallery-item">
                                    <img src="{{ $photo['url'] }}" alt="Station photo {{ $loop->iteration }}">
                                    <div class="station-gallery-caption">{{ $photo['caption'] ?: 'Station photo #' . $loop->iteration }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="station-grid">
                <div class="section-card">
                    <div class="section-header">
                        <div>
                            <div class="section-title"><i class="fa-solid fa-chart-column"></i> Station Snapshot</div>
                            <div class="section-subtitle">Current station counts based on roster, assigned crew members, equipment lines, and filed investigation reports.</div>
                        </div>
                    </div>
                    <div class="section-body">
                        <div class="stat-grid">
                            <div class="stat-block"><div class="stat-block-label">Station Officers</div><div class="stat-block-value">{{ $stationOfficers->count() }}</div></div>
                            <div class="stat-block"><div class="stat-block-label">Crew Members</div><div class="stat-block-value">{{ $crewMembers->count() }}</div></div>
                            <div class="stat-block"><div class="stat-block-label">Equipment Lines</div><div class="stat-block-value">{{ $equipmentAssignments->count() }}</div></div>
                            <div class="stat-block"><div class="stat-block-label">Spot Reports</div><div class="stat-block-value" id="spotReportsCount">{{ $spotReports->count() }}</div></div>
                            <div class="stat-block"><div class="stat-block-label">Progress Reports</div><div class="stat-block-value" id="progressReportsCount">{{ $progressReports->count() }}</div></div>
                            <div class="stat-block"><div class="stat-block-label">Final Reports</div><div class="stat-block-value" id="finalReportsCount">{{ $finalReports->count() }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-header">
                        <div>
                            <div class="section-title"><i class="fa-solid fa-people-group"></i> Crew Members</div>
                            <div class="section-subtitle">These members are derived from the user IDs stored in your assigned team slots.</div>
                        </div>
                    </div>
                    <div class="section-body">
                        @if($crewMembers->isEmpty())
                            <div class="empty-box">
                                <i class="fa-solid fa-user-slash"></i>
                                <div>No crew members are linked to your current response team yet.</div>
                            </div>
                        @else
                            <div class="crew-list">
                                @foreach($crewMembers as $member)
                                    <div class="crew-item">
                                        <div class="crew-avatar">{{ strtoupper(substr($member->name, 0, 1)) }}</div>
                                        <div>
                                            <div class="crew-name">{{ $member->name }}</div>
                                            <div class="crew-role">{{ $member->role_label }}{{ $member->role_name ? ' | ' . ucfirst($member->role_name) : '' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-id-card-clip"></i> Station Officers</div>
                        <div class="section-subtitle">All station officers whose <code>station_id</code> matches your assigned station.</div>
                    </div>
                </div>
                <div class="section-body table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Officer</th>
                                <th>Rank</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stationOfficers as $officer)
                                @php
                                    $status = strtolower($officer->status ?? 'available');
                                    $statusClass = match($status) {
                                        'available' => 'status-available',
                                        'busy', 'on-scene' => 'status-busy',
                                        'responding', 'dispatched' => 'status-dispatched',
                                        default => 'status-default',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="table-title">{{ trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')) ?: 'Unknown Officer' }}</div>
                                        <div class="table-muted">ID #{{ $officer->station_officer_id }}</div>
                                    </td>
                                    <td>{{ $officer->rank ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($officer->user->role->role ?? 'station officer') }}</td>
                                    <td>{{ $officer->user->contact_number ?? ($officer->user->email ?? 'N/A') }}</td>
                                    <td><span class="status-pill {{ $statusClass }}">{{ ucwords(str_replace('-', ' ', $status)) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-box">
                                            <i class="fa-solid fa-users-slash"></i>
                                            <div>No station officers found for this station.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-file-lines"></i> Report Management</div>
                        <div class="section-subtitle">Only spot, progress, and final investigation reports scoped to this station are shown here.</div>
                    </div>
                    <div class="action-row">
                        <button type="button" class="btn btn-outline" data-open-report-modal="spot"><i class="fa-solid fa-file-circle-plus"></i> New Spot</button>
                        <button type="button" class="btn btn-outline" data-open-report-modal="progress"><i class="fa-solid fa-file-circle-plus"></i> New Progress</button>
                        <button type="button" class="btn btn-primary" data-open-report-modal="final"><i class="fa-solid fa-file-circle-plus"></i> New Final</button>
                    </div>
                </div>

                <div class="report-tabs" id="reportTabs">
                    <button type="button" class="report-tab active" data-report-tab="spot">Spot Investigation</button>
                    <button type="button" class="report-tab" data-report-tab="progress">Progress Investigation</button>
                    <button type="button" class="report-tab" data-report-tab="final">Final Investigation</button>
                </div>

                <div class="report-panel active" data-report-panel="spot">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Incident</th>
                                    <th>Investigator</th>
                                    <th>Fire Timeline</th>
                                    <th>Damage</th>
                                    <th>Occupancy</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="spotReportsTableBody">
                                @forelse($spotReports as $report)
                                    @php $hasProgress = (bool) $report->progressInvestigation; @endphp
                                    <tr id="spot-report-{{ $report->spot_investigation_report_id }}">
                                        <td>
                                            <div class="table-title">Incident #{{ $report->incident_id }}</div>
                                            <div class="table-muted">{{ $report->incident?->marker?->title ?? 'Unknown incident location' }}</div>
                                        </td>
                                        <td>{{ trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: 'Unknown investigator' }}</td>
                                        <td>{{ optional($report->fire_started)->format('M d, Y h:i A') ?: 'N/A' }}<br>{{ optional($report->fire_out)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                        <td>PHP {{ number_format((float) ($report->estimated_damage ?? 0), 2) }}</td>
                                        <td>{{ ucfirst($report->occupancy ?? 'n/a') }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-action view" data-report-action="view" data-report-type="spot" data-report-id="{{ $report->spot_investigation_report_id }}">View</button>
                                                <button type="button" class="table-action edit" data-report-action="edit" data-report-type="spot" data-report-id="{{ $report->spot_investigation_report_id }}">Edit</button>
                                                @unless($hasProgress)
                                                    <button type="button" class="table-action delete" data-report-action="delete" data-report-type="spot" data-report-id="{{ $report->spot_investigation_report_id }}">Delete</button>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-box">
                                                <i class="fa-solid fa-file-circle-xmark"></i>
                                                <div>No spot investigation reports have been filed for this station yet.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-panel" data-report-panel="progress">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Spot Report</th>
                                    <th>Fire Origin</th>
                                    <th>Cause</th>
                                    <th>Responsible Arson</th>
                                    <th>Investigator</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="progressReportsTableBody">
                                @forelse($progressReports as $report)
                                    @php $hasFinal = (bool) $report->finalInvestigation; @endphp
                                    <tr id="progress-report-{{ $report->progress_investigation_report_id }}">
                                        <td>
                                            <div class="table-title">Spot #{{ $report->spot_investigation_id }}</div>
                                            <div class="table-muted">{{ $report->spotInvestigation?->incident?->marker?->title ?? 'Unknown incident location' }}</div>
                                        </td>
                                        <td>{{ $report->fireOrigin?->origin ?? 'N/A' }}</td>
                                        <td>{{ $report->fire_cause ?? 'N/A' }}</td>
                                        <td>{{ $report->responsible_arson ?? 'Not specified' }}</td>
                                        <td>{{ trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: 'Unknown investigator' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-action view" data-report-action="view" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">View</button>
                                                @unless($hasFinal)
                                                    <button type="button" class="table-action edit" data-report-action="edit" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">Edit</button>
                                                    <button type="button" class="table-action delete" data-report-action="delete" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">Delete</button>
                                                @endunless
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-box">
                                                <i class="fa-solid fa-file-circle-xmark"></i>
                                                <div>No progress investigation reports have been filed for this station yet.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-panel" data-report-panel="final">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Progress Report</th>
                                    <th>Exact Address</th>
                                    <th>Total Damage</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="finalReportsTableBody">
                                @forelse($finalReports as $report)
                                    <tr id="final-report-{{ $report->final_investigation_report_id }}">
                                        <td>
                                            <div class="table-title">Progress #{{ $report->progress_investigation_id }}</div>
                                            <div class="table-muted">{{ $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title ?? 'Unknown incident location' }}</div>
                                        </td>
                                        <td>{{ $report->exact_address ?? 'N/A' }}</td>
                                        <td>PHP {{ number_format((float) ($report->total_damage ?? 0), 2) }}</td>
                                        <td>{{ optional($report->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                                        <td>
                                            <div class="table-actions">
                                                <button type="button" class="table-action view" data-report-action="view" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">View</button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-box">
                                                <i class="fa-solid fa-file-circle-xmark"></i>
                                                <div>No final investigation reports have been filed for this station yet.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="section-card">
                <div class="section-header">
                    <div>
                        <div class="section-title"><i class="fa-solid fa-toolbox"></i> Station Equipment</div>
                        <div class="section-subtitle">Equipment records are loaded from the station-equipment assignments linked to this station.</div>
                    </div>
                </div>
                <div class="section-body">
                    @if($equipmentAssignments->isEmpty())
                        <div class="empty-box">
                            <i class="fa-solid fa-box-open"></i>
                            <div>No equipment assignments are linked to this station yet.</div>
                        </div>
                    @else
                        <div class="equipment-grid">
                            @foreach($equipmentAssignments as $assignment)
                                <div class="equipment-item">
                                    <div>
                                        <div class="equipment-name">{{ $assignment->equipment?->equipment_name ?? 'Unnamed equipment' }}</div>
                                        <div class="equipment-category">{{ $assignment->equipment?->category?->category_name ?? 'Uncategorized equipment' }}</div>
                                    </div>
                                    <div class="qty-pill qty-available">Available: {{ $assignment->available_quantity ?? 0 }}</div>
                                    <div class="qty-pill qty-maintenance">Maintenance: {{ $assignment->maintenance_quantity ?? 0 }}</div>
                                    <div class="qty-pill qty-damaged">Damaged: {{ $assignment->damaged_quantity ?? 0 }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </main>

    <div class="report-modal" id="spotReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Spot Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="spot"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="spotReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Incident</label>
                            <select name="incident_id" class="form-select" required>
                                <option value="">Select incident</option>
                                @foreach($availableSpotIncidents as $incident)
                                    @php
                                        $incidentMarkerTitle = trim((string) $incident->marker?->title);
                                        $incidentMarkerDescription = trim((string) $incident->marker?->description);
                                        $incidentHasGenericTitle = in_array(strtolower($incidentMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true);
                                        $incidentHasGenericDescription = in_array(strtolower($incidentMarkerDescription), ['fire emergency reported by citizen.', 'fire emergency reported by citizen', 'reported by citizen', 'emergency reported by citizen'], true);
                                        $incidentPrimaryLocation = in_array(strtolower($incidentMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true)
                                            ? ($incidentMarkerDescription ?: null)
                                            : ($incidentMarkerTitle ?: $incidentMarkerDescription);
                                        $incidentLocalityLabel = collect([
                                            $incident->resolved_dropdown_barangay_name ?? $incident->barangay?->barangay_name,
                                            $incident->resolved_dropdown_city_name ?? $incident->barangay?->city?->city_name,
                                        ])->filter()->join(', ');
                                        if ($incidentHasGenericTitle && $incidentHasGenericDescription) {
                                            $incidentPrimaryLocation = null;
                                        }
                                        $incidentLocationLabel = $incidentPrimaryLocation && $incidentLocalityLabel
                                            ? $incidentPrimaryLocation . ' - ' . $incidentLocalityLabel
                                            : ($incidentPrimaryLocation ?: ($incidentLocalityLabel ?: 'Reported incident location'));
                                    @endphp
                                    <option
                                        value="{{ $incident->incident_id }}"
                                        data-updated-at="{{ optional($incident->updated_at)->format('Y-m-d\TH:i') }}"
                                        data-location="{{ $incidentLocationLabel ?: 'Unknown location' }}"
                                    >
                                        {{ $incidentLocationLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dead</label>
                            <input type="number" min="0" step="1" name="dead" class="form-input" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Injured</label>
                            <input type="number" min="0" step="1" name="injured" class="form-input" value="0">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Owner</label>
                            <input type="text" name="owner" class="form-input" placeholder="Owner or responsible party">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Report Completed</label>
                            <input type="datetime-local" name="report_completed_at" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Memorandum For</label>
                            <input type="text" name="memorandum_for" class="form-input" placeholder="Head of office receiving the report">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Started</label>
                            <input type="datetime-local" name="fire_started" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Out</label>
                            <input type="datetime-local" name="fire_out" class="form-input" id="spotFireOutInput">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Place of Occurrence</label>
                            <input type="text" name="place_of_occurrence" class="form-input" placeholder="Street, barangay, city, landmark">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Damage</label>
                            <input type="number" min="0" step="0.01" name="estimated_damage" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Area</label>
                            <input type="number" min="0" step="0.01" name="estimated_area" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Affected Establishments</label>
                            <input type="number" min="0" step="1" name="affected_establishments_count" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Highest Alarm</label>
                            <input type="text" name="highest_alarm_level" class="form-input" placeholder="Example: 1st alarm, 2nd alarm">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Occupancy</label>
                            <select name="occupancy" class="form-select" required>
                                <option value="">Select occupancy</option>
                                <option value="residential">Residential</option>
                                <option value="commercial">Commercial</option>
                                <option value="industrial">Industrial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Involved Structure</label>
                            <input type="text" name="involved_structure" class="form-input" placeholder="House, warehouse, stall, office">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Establishment Name</label>
                            <input type="text" name="establishment_name" class="form-input" placeholder="Establishment or business name">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Occupant</label>
                            <input type="text" name="occupant" class="form-input" placeholder="Occupant or tenant name">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Raw Description / Field Notes</label>
                            <textarea name="description" class="form-textarea" rows="5" placeholder="Type quick notes in Tagalog or English about what happened, observed fire conditions, witness statements, actions taken, and other scene details"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Fatality Details</label>
                            <textarea name="fatality_details" class="form-textarea" placeholder="Initial identity, location found, and other casualty details"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Details of Investigation / Narrative</label>
                            <textarea name="details_of_investigation" class="form-textarea" rows="6" placeholder="Full fire incident narration, area affected, weather condition, observed conditions, and other initial findings"></textarea>
                            <div class="table-muted" style="margin-top: 0.55rem;">Use field notes first, then let the system draft a cleaner narrative that you can still edit.</div>
                            <div class="table-muted" style="margin-top: 0.35rem;">Saved incident context such as weather, reported time, and synced fire data will also be used when available.</div>
                            <button type="button" class="btn btn-outline" data-generate-spot-narrative style="margin-top: 0.85rem; width: fit-content;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Narrative
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Disposition</label>
                            <textarea name="disposition" class="form-textarea" rows="4" placeholder="Assessment, case status, and whether this will be elevated to a higher office"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Noted By</label>
                            <select name="noted_by_station_officer_id" class="form-select">
                                <option value="">Select officer</option>
                                @foreach($stationOfficers as $officer)
                                    <option value="{{ $officer->station_officer_id }}">
                                        {{ trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? '')) ?: ('Officer #' . $officer->station_officer_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Approved By</label>
                            <select name="approved_by_station_officer_id" class="form-select">
                                <option value="">Select officer</option>
                                @foreach($stationOfficers as $officer)
                                    <option value="{{ $officer->station_officer_id }}">
                                        {{ trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? '')) ?: ('Officer #' . $officer->station_officer_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="spot">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Spot Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="progressReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Progress Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="progress"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="progressReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Spot Investigation</label>
                            <select name="spot_investigation_id" class="form-select" required>
                                <option value="">Select spot investigation</option>
                                @foreach($availableSpotReports as $report)
                                    @php
                                        $spotMarkerTitle = trim((string) $report->incident?->marker?->title);
                                        $spotMarkerDescription = trim((string) $report->incident?->marker?->description);
                                        $spotHasGenericTitle = in_array(strtolower($spotMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true);
                                        $spotHasGenericDescription = in_array(strtolower($spotMarkerDescription), ['fire emergency reported by citizen.', 'fire emergency reported by citizen', 'reported by citizen', 'emergency reported by citizen'], true);
                                        $spotPrimaryLocation = in_array(strtolower($spotMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true)
                                            ? ($spotMarkerDescription ?: null)
                                            : ($spotMarkerTitle ?: $spotMarkerDescription);
                                        $spotLocalityLabel = collect([
                                            $report->incident?->barangay?->barangay_name,
                                            $report->incident?->barangay?->city?->city_name,
                                        ])->filter()->join(', ');
                                        if ($spotHasGenericTitle && $spotHasGenericDescription) {
                                            $spotPrimaryLocation = null;
                                        }
                                        $spotLocationLabel = $spotPrimaryLocation && $spotLocalityLabel
                                            ? $spotPrimaryLocation . ' - ' . $spotLocalityLabel
                                            : ($spotPrimaryLocation ?: ($spotLocalityLabel ?: 'Reported incident location'));
                                    @endphp
                                    <option
                                        value="{{ $report->spot_investigation_report_id }}"
                                        data-location="{{ $spotLocationLabel ?: 'Unknown incident location' }}"
                                    >
                                        {{ $spotLocationLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Origin</label>
                            <select name="fire_origin_id" class="form-select">
                                <option value="">Select fire origin</option>
                                @foreach($fireOrigins as $origin)
                                    <option value="{{ $origin->fire_origin_id }}">{{ $origin->origin ?? ('Origin #' . $origin->fire_origin_id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Responsible Arson</label>
                            <input type="text" name="responsible_arson" class="form-input" placeholder="Optional arson subject">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Fire Cause</label>
                            <textarea name="fire_cause" class="form-textarea" required placeholder="Enter investigation findings and fire cause"></textarea>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="progress">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Progress Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="finalReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Final Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="final"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="finalReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Progress Investigation</label>
                            <select name="progress_investigation_id" class="form-select" required>
                                <option value="">Select progress investigation</option>
                                @foreach($availableProgressReports as $report)
                                    @php
                                        $progressMarkerTitle = trim((string) $report->spotInvestigation?->incident?->marker?->title);
                                        $progressMarkerDescription = trim((string) $report->spotInvestigation?->incident?->marker?->description);
                                        $progressHasGenericTitle = in_array(strtolower($progressMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true);
                                        $progressHasGenericDescription = in_array(strtolower($progressMarkerDescription), ['fire emergency reported by citizen.', 'fire emergency reported by citizen', 'reported by citizen', 'emergency reported by citizen'], true);
                                        $progressPrimaryLocation = in_array(strtolower($progressMarkerTitle), ['help emergency', 'fire emergency', 'emergency', 'incident'], true)
                                            ? ($progressMarkerDescription ?: null)
                                            : ($progressMarkerTitle ?: $progressMarkerDescription);
                                        $progressLocalityLabel = collect([
                                            $report->spotInvestigation?->incident?->barangay?->barangay_name,
                                            $report->spotInvestigation?->incident?->barangay?->city?->city_name,
                                        ])->filter()->join(', ');
                                        if ($progressHasGenericTitle && $progressHasGenericDescription) {
                                            $progressPrimaryLocation = null;
                                        }
                                        $progressLocationLabel = $progressPrimaryLocation && $progressLocalityLabel
                                            ? $progressPrimaryLocation . ' - ' . $progressLocalityLabel
                                            : ($progressPrimaryLocation ?: ($progressLocalityLabel ?: 'Reported incident location'));
                                    @endphp
                                    <option
                                        value="{{ $report->progress_investigation_report_id }}"
                                        data-location="{{ $progressLocationLabel ?: 'Unknown incident location' }}"
                                    >
                                        {{ $progressLocationLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Damage</label>
                            <input type="number" min="0" step="0.01" name="total_damage" class="form-input" required>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Exact Address</label>
                            <textarea name="exact_address" class="form-textarea" required placeholder="Enter the exact address for the final investigation report"></textarea>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="final">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Final Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="manageReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title" id="manageReportModalTitle">Report Details</div>
                <button type="button" class="report-close" data-close-manage-modal><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="report-modal-body" id="manageReportModalBody"></div>
            <div class="report-modal-footer" id="manageReportModalFooter"></div>
        </div>
    </div>

    <div class="toast-stack" id="reportToastStack"></div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const spotForm = document.getElementById('spotReportForm');
        const progressForm = document.getElementById('progressReportForm');
        const finalForm = document.getElementById('finalReportForm');
        const reportTabs = document.getElementById('reportTabs');
        const currentOfficerName = @json($currentOfficerName);
        const manageReportModal = document.getElementById('manageReportModal');
        const manageReportModalTitle = document.getElementById('manageReportModalTitle');
        const manageReportModalBody = document.getElementById('manageReportModalBody');
        const manageReportModalFooter = document.getElementById('manageReportModalFooter');
        const reportToastStack = document.getElementById('reportToastStack');
        const sirWordRouteTemplate = @json(route('firefighter.spot-reports.sir.word', ['spotReportId' => '__SPOT_REPORT_ID__']));

        const reportStore = {
            spot: @json($spotReportStore),
            progress: @json($progressReportStore),
            final: @json($finalReportStore),
        };

        function buildSirRoute(template, reportId) {
            return template.replace('__SPOT_REPORT_ID__', encodeURIComponent(reportId));
        }

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(value);
            return div.innerHTML;
        }

        function showToast(message, type = 'success', timeout = 3200) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, type);
                return;
            }

            if (!reportToastStack) {
                console[type === 'error' ? 'error' : 'log'](message);
                return;
            }

            const toast = document.createElement('div');
            toast.className = `report-toast ${type}`;
            toast.textContent = message;
            reportToastStack.appendChild(toast);

            window.setTimeout(() => {
                toast.remove();
            }, timeout);
        }

        function openReportModal(type) {
            document.getElementById(`${type}ReportModal`)?.classList.add('active');
        }

        function closeReportModal(type) {
            document.getElementById(`${type}ReportModal`)?.classList.remove('active');
        }

        function openManageModal(title) {
            manageReportModalTitle.textContent = title;
            manageReportModal.classList.add('active');
        }

        function closeManageModal() {
            manageReportModal.classList.remove('active');
            manageReportModalBody.innerHTML = '';
            manageReportModalFooter.innerHTML = '';
        }

        function switchReportTab(type) {
            document.querySelectorAll('.report-tab').forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.reportTab === type);
            });
            document.querySelectorAll('.report-panel').forEach((panel) => {
                panel.classList.toggle('active', panel.dataset.reportPanel === type);
            });
        }

        function formatDateTime(value) {
            if (!value) return 'N/A';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString(undefined, {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
        }

        function formatMoney(value) {
            return `PHP ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        function incrementCounter(id) {
            const element = document.getElementById(id);
            if (!element) return;
            const count = Number.parseInt(element.textContent || '0', 10);
            element.textContent = Number.isNaN(count) ? '1' : String(count + 1);
        }

        function decrementCounter(id) {
            const element = document.getElementById(id);
            if (!element) return;
            const count = Number.parseInt(element.textContent || '0', 10);
            element.textContent = String(Math.max(0, Number.isNaN(count) ? 0 : count - 1));
        }

        function removeOption(select, value) {
            if (!select) return;
            const option = Array.from(select.options).find((item) => item.value === String(value));
            option?.remove();
        }

        function getIncidentLocationFromForm(form) {
            const incidentSelect = form?.querySelector('select[name="incident_id"]');
            const incidentOption = incidentSelect?.options?.[incidentSelect.selectedIndex];

            if (incidentOption?.value) {
                return incidentOption.dataset.location || incidentOption.textContent?.trim() || '';
            }

            return form?.dataset?.incidentLocation || '';
        }

        async function generateSpotNarrative(form, triggerButton) {
            const narrativeField = form?.querySelector('textarea[name="details_of_investigation"]');
            if (!form || !narrativeField) return;

            const formData = new FormData(form);
            const payload = {
                incident_id: formData.get('incident_id') || form?.dataset?.incidentId || '',
                description: formData.get('description') || '',
                details_of_investigation: formData.get('details_of_investigation') || '',
                disposition: formData.get('disposition') || '',
                place_of_occurrence: formData.get('place_of_occurrence') || '',
                incident_location: getIncidentLocationFromForm(form),
                fire_started: formData.get('fire_started') || '',
                fire_out: formData.get('fire_out') || '',
                occupancy: formData.get('occupancy') || '',
                involved_structure: formData.get('involved_structure') || '',
                establishment_name: formData.get('establishment_name') || '',
                owner: formData.get('owner') || '',
                occupant: formData.get('occupant') || '',
                estimated_damage: formData.get('estimated_damage') || '',
                estimated_area: formData.get('estimated_area') || '',
                highest_alarm_level: formData.get('highest_alarm_level') || '',
                dead: formData.get('dead') || '',
                injured: formData.get('injured') || '',
                fatality_details: formData.get('fatality_details') || '',
            };

            const hasUsefulInput = Object.values(payload).some((value) => String(value || '').trim() !== '');
            if (!hasUsefulInput) {
                showToast('Add some field notes or incident details first before generating a narrative.', 'error');
                return;
            }

            const originalHtml = triggerButton.innerHTML;
            triggerButton.disabled = true;
            triggerButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Drafting...';
            showToast('Generating a cleaner narrative draft...', 'info', 1800);

            try {
                const response = await fetch('/api/spot-investigation/draft-narrative', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Failed to generate a narrative draft.');
                }

                narrativeField.value = result.data?.narrative || '';
                showToast('Narrative draft generated. You can still edit it before saving.', 'success');
            } catch (error) {
                console.error('Narrative draft failed:', error);
                showToast(error.message || 'Failed to generate a narrative draft.', 'error');
            } finally {
                triggerButton.disabled = false;
                triggerButton.innerHTML = originalHtml;
            }
        }

        function bindSpotNarrativeButtons(scope = document) {
            scope.querySelectorAll('[data-generate-spot-narrative]').forEach((button) => {
                if (button.dataset.boundNarrativeButton === 'true') return;
                button.dataset.boundNarrativeButton = 'true';
                button.addEventListener('click', () => {
                    const form = button.closest('form');
                    generateSpotNarrative(form, button);
                });
            });
        }

        function getSelectedOptionText(select) {
            const option = select?.options?.[select.selectedIndex];
            return option?.dataset?.location || option?.textContent?.trim() || 'Unknown incident location';
        }

        function createActionButtons(type, report) {
            const actions = [
                `<button type="button" class="table-action view" data-report-action="view" data-report-type="${type}" data-report-id="${report.id}">View</button>`,
            ];

            if (type === 'spot') {
                actions.push(`<a class="table-action edit" href="${buildSirRoute(sirWordRouteTemplate, report.id)}">Word</a>`);
                actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
                if (!report.has_progress) {
                    actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
                }
            }

            if (type === 'progress' && !report.has_final) {
                actions.push(`<button type="button" class="table-action edit" data-report-action="edit" data-report-type="${type}" data-report-id="${report.id}">Edit</button>`);
                actions.push(`<button type="button" class="table-action delete" data-report-action="delete" data-report-type="${type}" data-report-id="${report.id}">Delete</button>`);
            }

            return `<div class="table-actions">${actions.join('')}</div>`;
        }

        function appendSpotReportRow(report) {
            const tbody = document.getElementById('spotReportsTableBody');
            if (!tbody || !report?.id) return;

            const emptyState = tbody.querySelector('td[colspan="6"]')?.closest('tr');
            if (emptyState) emptyState.remove();

            tbody.insertAdjacentHTML('afterbegin', `
                <tr id="spot-report-${report.id}">
                    <td><div class="table-title">Incident #${escapeHtml(report.incident_id)}</div><div class="table-muted">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></td>
                    <td>${escapeHtml(report.investigator_name || currentOfficerName)}</td>
                    <td>${escapeHtml(formatDateTime(report.fire_started))}<br>${escapeHtml(formatDateTime(report.fire_out))}</td>
                    <td>${escapeHtml(formatMoney(report.estimated_damage))}</td>
                    <td>${escapeHtml(String(report.occupancy || 'n/a').replace(/^./, (match) => match.toUpperCase()))}</td>
                    <td>${createActionButtons('spot', report)}</td>
                </tr>
            `);

            incrementCounter('spotReportsCount');
        }

        function appendProgressReportRow(report) {
            const tbody = document.getElementById('progressReportsTableBody');
            if (!tbody || !report?.id) return;

            const emptyState = tbody.querySelector('td[colspan="6"]')?.closest('tr');
            if (emptyState) emptyState.remove();

            tbody.insertAdjacentHTML('afterbegin', `
                <tr id="progress-report-${report.id}">
                    <td><div class="table-title">Spot #${escapeHtml(report.spot_investigation_id)}</div><div class="table-muted">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></td>
                    <td>${escapeHtml(report.fire_origin_name || 'N/A')}</td>
                    <td>${escapeHtml(report.fire_cause || 'N/A')}</td>
                    <td>${escapeHtml(report.responsible_arson || 'Not specified')}</td>
                    <td>${escapeHtml(report.investigator_name || currentOfficerName)}</td>
                    <td>${createActionButtons('progress', report)}</td>
                </tr>
            `);

            incrementCounter('progressReportsCount');
        }

        function appendFinalReportRow(report) {
            const tbody = document.getElementById('finalReportsTableBody');
            if (!tbody || !report?.id) return;

            const emptyState = tbody.querySelector('td[colspan="5"]')?.closest('tr');
            if (emptyState) emptyState.remove();

            tbody.insertAdjacentHTML('afterbegin', `
                <tr id="final-report-${report.id}">
                    <td><div class="table-title">Progress #${escapeHtml(report.progress_investigation_id)}</div><div class="table-muted">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></td>
                    <td>${escapeHtml(report.exact_address || 'N/A')}</td>
                    <td>${escapeHtml(formatMoney(report.total_damage))}</td>
                    <td>${escapeHtml(formatDateTime(report.created_at))}</td>
                    <td>${createActionButtons('final', report)}</td>
                </tr>
            `);

            incrementCounter('finalReportsCount');
        }

        function replaceRow(type, report) {
            const row = document.getElementById(`${type}-report-${report.id}`);
            if (!row) return;
            row.remove();
            if (type === 'spot') appendSpotReportRow(report);
            if (type === 'progress') appendProgressReportRow(report);
            if (type === 'final') appendFinalReportRow(report);
        }

        function renderViewDetails(type, report) {
            if (type === 'spot') {
                return `
                    <div class="detail-grid">
                        <div class="detail-item"><div class="detail-label">Incident</div><div class="detail-value">Incident #${escapeHtml(report.incident_id)}</div></div>
                        <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                        <div class="detail-item"><div class="detail-label">Investigator</div><div class="detail-value">${escapeHtml(report.investigator_name || currentOfficerName)}</div></div>
                        <div class="detail-item"><div class="detail-label">Occupancy</div><div class="detail-value">${escapeHtml(report.occupancy || 'N/A')}</div></div>
                        <div class="detail-item"><div class="detail-label">Fire Started</div><div class="detail-value">${escapeHtml(formatDateTime(report.fire_started))}</div></div>
                        <div class="detail-item"><div class="detail-label">Fire Out</div><div class="detail-value">${escapeHtml(formatDateTime(report.fire_out))}</div></div>
                        <div class="detail-item"><div class="detail-label">Owner</div><div class="detail-value">${escapeHtml(report.owner || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Report Completed</div><div class="detail-value">${escapeHtml(formatDateTime(report.report_completed_at))}</div></div>
                        <div class="detail-item"><div class="detail-label">Memorandum For</div><div class="detail-value">${escapeHtml(report.memorandum_for || 'Not provided')}</div></div>
                        <div class="detail-item full"><div class="detail-label">Place of Occurrence</div><div class="detail-value">${escapeHtml(report.place_of_occurrence || report.incident_location || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Estimated Damage</div><div class="detail-value">${escapeHtml(formatMoney(report.estimated_damage))}</div></div>
                        <div class="detail-item"><div class="detail-label">Estimated Area</div><div class="detail-value">${escapeHtml(report.estimated_area || '0')}</div></div>
                        <div class="detail-item"><div class="detail-label">Affected Establishments</div><div class="detail-value">${escapeHtml(report.affected_establishments_count || 0)}</div></div>
                        <div class="detail-item"><div class="detail-label">Casualties</div><div class="detail-value">Dead: ${escapeHtml(report.dead || 0)}\nInjured: ${escapeHtml(report.injured || 0)}</div></div>
                        <div class="detail-item"><div class="detail-label">Highest Alarm</div><div class="detail-value">${escapeHtml(report.highest_alarm_level || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Involved Structure</div><div class="detail-value">${escapeHtml(report.involved_structure || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Establishment</div><div class="detail-value">${escapeHtml(report.establishment_name || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Occupant</div><div class="detail-value">${escapeHtml(report.occupant || 'Not provided')}</div></div>
                        <div class="detail-item full"><div class="detail-label">Raw Description / Field Notes</div><div class="detail-value">${escapeHtml(report.description || 'Not provided')}</div></div>
                        <div class="detail-item full"><div class="detail-label">Fatality Details</div><div class="detail-value">${escapeHtml(report.fatality_details || 'Not provided')}</div></div>
                        <div class="detail-item full"><div class="detail-label">Narrative</div><div class="detail-value">${escapeHtml(report.details_of_investigation || 'Not provided')}</div></div>
                        <div class="detail-item full"><div class="detail-label">Disposition</div><div class="detail-value">${escapeHtml(report.disposition || 'Not provided')}</div></div>
                        <div class="detail-item"><div class="detail-label">Noted By</div><div class="detail-value">${escapeHtml(report.noted_by_name || 'Not assigned')}</div></div>
                        <div class="detail-item"><div class="detail-label">Approved By</div><div class="detail-value">${escapeHtml(report.approved_by_name || 'Not assigned')}</div></div>
                    </div>
                `;
            }

            if (type === 'progress') {
                return `
                    <div class="detail-grid">
                        <div class="detail-item"><div class="detail-label">Spot Report</div><div class="detail-value">Spot #${escapeHtml(report.spot_investigation_id)}</div></div>
                        <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                        <div class="detail-item"><div class="detail-label">Fire Origin</div><div class="detail-value">${escapeHtml(report.fire_origin_name || 'N/A')}</div></div>
                        <div class="detail-item"><div class="detail-label">Responsible Arson</div><div class="detail-value">${escapeHtml(report.responsible_arson || 'Not specified')}</div></div>
                        <div class="detail-item"><div class="detail-label">Investigator</div><div class="detail-value">${escapeHtml(report.investigator_name || currentOfficerName)}</div></div>
                        <div class="detail-item full"><div class="detail-label">Fire Cause</div><div class="detail-value">${escapeHtml(report.fire_cause || 'N/A')}</div></div>
                    </div>
                `;
            }

            return `
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Progress Report</div><div class="detail-value">Progress #${escapeHtml(report.progress_investigation_id)}</div></div>
                    <div class="detail-item"><div class="detail-label">Location</div><div class="detail-value">${escapeHtml(report.incident_location || 'Unknown incident location')}</div></div>
                    <div class="detail-item"><div class="detail-label">Total Damage</div><div class="detail-value">${escapeHtml(formatMoney(report.total_damage))}</div></div>
                    <div class="detail-item"><div class="detail-label">Created</div><div class="detail-value">${escapeHtml(formatDateTime(report.created_at))}</div></div>
                    <div class="detail-item"><div class="detail-label">Fire Origin</div><div class="detail-value">${escapeHtml(report.fire_origin_name || 'N/A')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Exact Address</div><div class="detail-value">${escapeHtml(report.exact_address || 'N/A')}</div></div>
                    <div class="detail-item full"><div class="detail-label">Fire Cause</div><div class="detail-value">${escapeHtml(report.fire_cause || 'N/A')}</div></div>
                </div>
            `;
        }

        function renderEditForm(type, report) {
            if (type === 'spot') {
                return `
                    <form id="manageReportEditForm" class="form-grid">
                        <div class="form-group"><label class="form-label">Dead</label><input class="form-input" type="number" name="dead" min="0" step="1" value="${escapeHtml(report.dead || 0)}"></div>
                        <div class="form-group"><label class="form-label">Injured</label><input class="form-input" type="number" name="injured" min="0" step="1" value="${escapeHtml(report.injured || 0)}"></div>
                        <div class="form-group full"><label class="form-label">Owner</label><input class="form-input" type="text" name="owner" value="${escapeHtml(report.owner || '')}"></div>
                        <div class="form-group"><label class="form-label">Report Completed</label><input class="form-input" type="datetime-local" name="report_completed_at" value="${escapeHtml((report.report_completed_at || '').slice(0, 16))}"></div>
                        <div class="form-group"><label class="form-label">Memorandum For</label><input class="form-input" type="text" name="memorandum_for" value="${escapeHtml(report.memorandum_for || '')}"></div>
                        <div class="form-group"><label class="form-label">Fire Started</label><input class="form-input" type="datetime-local" name="fire_started" value="${escapeHtml((report.fire_started || '').slice(0, 16))}"></div>
                        <div class="form-group"><label class="form-label">Fire Out</label><input class="form-input" type="datetime-local" name="fire_out" value="${escapeHtml((report.fire_out || '').slice(0, 16))}"></div>
                        <div class="form-group full"><label class="form-label">Place of Occurrence</label><input class="form-input" type="text" name="place_of_occurrence" value="${escapeHtml(report.place_of_occurrence || '')}"></div>
                        <div class="form-group"><label class="form-label">Estimated Damage</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_damage" value="${escapeHtml(report.estimated_damage || 0)}" required></div>
                        <div class="form-group"><label class="form-label">Estimated Area</label><input class="form-input" type="number" min="0" step="0.01" name="estimated_area" value="${escapeHtml(report.estimated_area || 0)}" required></div>
                        <div class="form-group"><label class="form-label">Affected Establishments</label><input class="form-input" type="number" min="0" step="1" name="affected_establishments_count" value="${escapeHtml(report.affected_establishments_count || 0)}"></div>
                        <div class="form-group"><label class="form-label">Highest Alarm</label><input class="form-input" type="text" name="highest_alarm_level" value="${escapeHtml(report.highest_alarm_level || '')}"></div>
                        <div class="form-group full">
                            <label class="form-label">Occupancy</label>
                            <select class="form-select" name="occupancy" required>
                                <option value="residential" ${report.occupancy === 'residential' ? 'selected' : ''}>Residential</option>
                                <option value="commercial" ${report.occupancy === 'commercial' ? 'selected' : ''}>Commercial</option>
                                <option value="industrial" ${report.occupancy === 'industrial' ? 'selected' : ''}>Industrial</option>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label">Involved Structure</label><input class="form-input" type="text" name="involved_structure" value="${escapeHtml(report.involved_structure || '')}"></div>
                        <div class="form-group"><label class="form-label">Establishment Name</label><input class="form-input" type="text" name="establishment_name" value="${escapeHtml(report.establishment_name || '')}"></div>
                        <div class="form-group full"><label class="form-label">Occupant</label><input class="form-input" type="text" name="occupant" value="${escapeHtml(report.occupant || '')}"></div>
                        <div class="form-group full"><label class="form-label">Raw Description / Field Notes</label><textarea class="form-textarea" name="description" rows="5">${escapeHtml(report.description || '')}</textarea></div>
                        <div class="form-group full"><label class="form-label">Fatality Details</label><textarea class="form-textarea" name="fatality_details">${escapeHtml(report.fatality_details || '')}</textarea></div>
                        <div class="form-group full">
                            <label class="form-label">Narrative</label>
                            <textarea class="form-textarea" name="details_of_investigation">${escapeHtml(report.details_of_investigation || '')}</textarea>
                            <div class="table-muted" style="margin-top: 0.55rem;">Generate a refined narrative from field notes, then adjust the wording before saving.</div>
                            <div class="table-muted" style="margin-top: 0.35rem;">Saved incident context such as weather, reported time, and synced fire data will also be used when available.</div>
                            <button type="button" class="btn btn-outline" data-generate-spot-narrative style="margin-top: 0.85rem; width: fit-content;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Narrative
                            </button>
                        </div>
                        <div class="form-group full"><label class="form-label">Disposition</label><textarea class="form-textarea" name="disposition">${escapeHtml(report.disposition || '')}</textarea></div>
                        <div class="form-group">
                            <label class="form-label">Noted By</label>
                            <select class="form-select" name="noted_by_station_officer_id">
                                <option value="">Select officer</option>
                                @foreach($stationOfficers as $officer)
                                    <option value="{{ $officer->station_officer_id }}" ${String(report.noted_by_station_officer_id || '') === '{{ $officer->station_officer_id }}' ? 'selected' : ''}>
                                        {{ trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? '')) ?: ('Officer #' . $officer->station_officer_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Approved By</label>
                            <select class="form-select" name="approved_by_station_officer_id">
                                <option value="">Select officer</option>
                                @foreach($stationOfficers as $officer)
                                    <option value="{{ $officer->station_officer_id }}" ${String(report.approved_by_station_officer_id || '') === '{{ $officer->station_officer_id }}' ? 'selected' : ''}>
                                        {{ trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? '')) ?: ('Officer #' . $officer->station_officer_id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                `;
            }

            return `
                <form id="manageReportEditForm" class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Fire Origin</label>
                        <select class="form-select" name="fire_origin_id">
                            <option value="">Select fire origin</option>
                            @foreach($fireOrigins as $origin)
                                <option value="{{ $origin->fire_origin_id }}">{{ $origin->origin ?? ('Origin #' . $origin->fire_origin_id) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsible Arson</label>
                        <input class="form-input" type="text" name="responsible_arson" value="${escapeHtml(report.responsible_arson || '')}">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Fire Cause</label>
                        <textarea class="form-textarea" name="fire_cause" required>${escapeHtml(report.fire_cause || '')}</textarea>
                    </div>
                </form>
            `;
        }

        function openViewModal(type, report) {
            openManageModal(`${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
            window.logUserInteraction?.(`viewed ${type} report details`);
            manageReportModalBody.innerHTML = renderViewDetails(type, report);
            manageReportModalFooter.innerHTML = type === 'spot'
                ? `
                    <a class="btn btn-outline" href="${buildSirRoute(sirWordRouteTemplate, report.id)}">Download Word</a>
                    <button type="button" class="btn btn-outline" data-close-manage-modal>Close</button>
                `
                : '<button type="button" class="btn btn-outline" data-close-manage-modal>Close</button>';
        }

        function openEditModal(type, report) {
            openManageModal(`Edit ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
            manageReportModalBody.innerHTML = renderEditForm(type, report);
            if (type === 'spot') {
                const editForm = manageReportModalBody.querySelector('#manageReportEditForm');
                if (editForm) {
                    editForm.dataset.incidentLocation = report.incident_location || '';
                    editForm.dataset.incidentId = report.incident_id || '';
                }
                bindSpotNarrativeButtons(manageReportModalBody);
            }
            if (type === 'progress') {
                const fireOriginInput = manageReportModalBody.querySelector('select[name="fire_origin_id"]');
                if (fireOriginInput) {
                    fireOriginInput.value = report.fire_origin_id ? String(report.fire_origin_id) : '';
                }
            }
            manageReportModalFooter.innerHTML = `
                <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
                <button type="button" class="btn btn-primary" id="saveManagedReportButton"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            `;

            document.getElementById('saveManagedReportButton')?.addEventListener('click', () => saveManagedReport(type, report.id));
        }

        async function saveManagedReport(type, reportId) {
            const form = document.getElementById('manageReportEditForm');
            if (!form) return;

            const submitButton = document.getElementById('saveManagedReportButton');
            const originalHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            showToast('Saving report changes...', 'info', 1800);

            try {
                const formData = new FormData(form);
                const response = await fetch(`/api/${type}-investigation/${reportId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-HTTP-Method-Override': 'PUT',
                    },
                    body: formData,
                });

                const result = await response.json();
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Failed to update report.');
                }

                const existing = reportStore[type][reportId];
                const updated = { ...existing };

                if (type === 'spot') {
                    updated.dead = formData.get('dead');
                    updated.injured = formData.get('injured');
                    updated.owner = formData.get('owner');
                    updated.report_completed_at = formData.get('report_completed_at');
                    updated.memorandum_for = formData.get('memorandum_for');
                    updated.fire_started = formData.get('fire_started');
                    updated.fire_out = formData.get('fire_out');
                    updated.place_of_occurrence = formData.get('place_of_occurrence');
                    updated.estimated_damage = formData.get('estimated_damage');
                    updated.estimated_area = formData.get('estimated_area');
                    updated.affected_establishments_count = formData.get('affected_establishments_count');
                    updated.highest_alarm_level = formData.get('highest_alarm_level');
                    updated.occupancy = formData.get('occupancy');
                    updated.involved_structure = formData.get('involved_structure');
                    updated.establishment_name = formData.get('establishment_name');
                    updated.occupant = formData.get('occupant');
                    updated.description = formData.get('description');
                    updated.fatality_details = formData.get('fatality_details');
                    updated.details_of_investigation = formData.get('details_of_investigation');
                    updated.disposition = formData.get('disposition');
                    updated.noted_by_station_officer_id = formData.get('noted_by_station_officer_id');
                    updated.approved_by_station_officer_id = formData.get('approved_by_station_officer_id');
                    updated.noted_by_name = manageReportModalBody.querySelector('select[name="noted_by_station_officer_id"]')?.selectedOptions?.[0]?.textContent?.trim() || '';
                    updated.approved_by_name = manageReportModalBody.querySelector('select[name="approved_by_station_officer_id"]')?.selectedOptions?.[0]?.textContent?.trim() || '';
                } else {
                    updated.fire_origin_id = formData.get('fire_origin_id');
                    updated.fire_origin_name = manageReportModalBody.querySelector('select[name="fire_origin_id"]')?.selectedOptions?.[0]?.textContent?.trim() || 'N/A';
                    updated.responsible_arson = formData.get('responsible_arson');
                    updated.fire_cause = formData.get('fire_cause');
                }

                reportStore[type][reportId] = updated;
                replaceRow(type, updated);
                closeManageModal();
                showToast('Report updated successfully.', 'success');
            } catch (error) {
                console.error('Update failed:', error);
                showToast(error.message || 'Failed to update report.', 'error');
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalHtml;
            }
        }

        async function deleteManagedReport(type, reportId) {
            const report = reportStore[type][reportId];
            if (!report) return;

            openManageModal(`Delete ${type.charAt(0).toUpperCase() + type.slice(1)} Investigation Report`);
            manageReportModalBody.innerHTML = `
                <div class="detail-item">
                    <div class="detail-label">Delete Report</div>
                    <div class="detail-value">This will permanently remove the ${type} investigation report for ${escapeHtml(report.incident_location || `record #${reportId}`)}.</div>
                </div>
            `;
            manageReportModalFooter.innerHTML = `
                <button type="button" class="btn btn-outline" data-close-manage-modal>Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReportButton"><i class="fa-solid fa-trash"></i> Delete Report</button>
            `;

            document.getElementById('confirmDeleteReportButton')?.addEventListener('click', async () => {
                const deleteButton = document.getElementById('confirmDeleteReportButton');
                const originalHtml = deleteButton.innerHTML;
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
                showToast('Deleting report...', 'info', 1800);

                try {
                    const response = await fetch(`/api/${type}-investigation/${reportId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-HTTP-Method-Override': 'DELETE',
                        },
                    });

                    const result = await response.json();
                    if (!response.ok || result.success === false) {
                        throw new Error(result.message || 'Failed to delete report.');
                    }

                    document.getElementById(`${type}-report-${reportId}`)?.remove();
                    delete reportStore[type][reportId];
                    decrementCounter(`${type}ReportsCount`);
                    closeManageModal();
                    showToast('Report deleted successfully.', 'success');
                } catch (error) {
                    console.error('Delete failed:', error);
                    showToast(error.message || 'Failed to delete report.', 'error');
                } finally {
                    deleteButton.disabled = false;
                    deleteButton.innerHTML = originalHtml;
                }
            });
        }

        async function submitReportForm(form, endpoint, mapSuccessData, handleSuccess) {
            const submitButton = form.querySelector('button[type="submit"]');
            const originalHtml = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            showToast('Submitting report...', 'info', 1800);

            try {
                const formData = new FormData(form);
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const result = await response.json();
                if (!response.ok || result.success === false) {
                    throw new Error(result.message || 'Failed to submit report.');
                }

                const mapped = mapSuccessData(formData, result.data || {});
                handleSuccess(mapped);
                form.reset();
                showToast('Investigation report submitted successfully.', 'success');
                return mapped;
            } catch (error) {
                console.error('Investigation report submission failed:', error);
                showToast(error.message || 'Failed to submit report.', 'error');
                return null;
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalHtml;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-open-report-modal]').forEach((button) => {
                button.addEventListener('click', () => openReportModal(button.dataset.openReportModal));
            });

            document.querySelectorAll('[data-close-report-modal]').forEach((button) => {
                button.addEventListener('click', () => closeReportModal(button.dataset.closeReportModal));
            });

            document.addEventListener('click', (event) => {
                if (event.target.matches('[data-close-manage-modal]')) {
                    closeManageModal();
                    return;
                }

                const actionButton = event.target.closest('[data-report-action]');
                if (!actionButton) return;

                const { reportAction, reportType, reportId } = actionButton.dataset;
                const report = reportStore[reportType]?.[reportId];
                if (!report) return;

                if (reportAction === 'view') openViewModal(reportType, report);
                if (reportAction === 'edit') openEditModal(reportType, report);
                if (reportAction === 'delete') deleteManagedReport(reportType, reportId);
            });

            document.querySelectorAll('.report-modal').forEach((modal) => {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        modal.classList.remove('active');
                    }
                });
            });

            reportTabs?.addEventListener('click', (event) => {
                const tab = event.target.closest('.report-tab');
                if (!tab) return;
                switchReportTab(tab.dataset.reportTab);
            });

            const spotIncidentSelect = spotForm?.querySelector('select[name="incident_id"]');
            const progressSpotSelect = progressForm?.querySelector('select[name="spot_investigation_id"]');
            const progressFireOriginSelect = progressForm?.querySelector('select[name="fire_origin_id"]');
            const finalProgressSelect = finalForm?.querySelector('select[name="progress_investigation_id"]');

            bindSpotNarrativeButtons();

            spotIncidentSelect?.addEventListener('change', (event) => {
                const selectedOption = event.target.options[event.target.selectedIndex];
                const fireOutInput = document.getElementById('spotFireOutInput');
                if (fireOutInput && selectedOption?.dataset?.updatedAt) {
                    fireOutInput.value = selectedOption.dataset.updatedAt;
                }
            });

            spotForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const selectedIncidentId = spotIncidentSelect?.value;
                const selectedLocation = getSelectedOptionText(spotIncidentSelect);
                const mapped = await submitReportForm(
                    spotForm,
                    '/api/spot-investigation',
                    (formData, data) => ({
                        ...data,
                        id: data.spot_investigation_report_id,
                        type: 'spot',
                        incident_id: formData.get('incident_id'),
                        incident_location: selectedLocation,
                        investigator_name: currentOfficerName,
                        fire_started: formData.get('fire_started'),
                        fire_out: formData.get('fire_out'),
                        estimated_damage: formData.get('estimated_damage'),
                        occupancy: formData.get('occupancy'),
                        estimated_area: formData.get('estimated_area'),
                        dead: formData.get('dead'),
                        injured: formData.get('injured'),
                        owner: formData.get('owner'),
                        report_completed_at: formData.get('report_completed_at'),
                        memorandum_for: formData.get('memorandum_for'),
                        place_of_occurrence: formData.get('place_of_occurrence'),
                        involved_structure: formData.get('involved_structure'),
                        establishment_name: formData.get('establishment_name'),
                        occupant: formData.get('occupant'),
                        highest_alarm_level: formData.get('highest_alarm_level'),
                        affected_establishments_count: formData.get('affected_establishments_count'),
                        description: formData.get('description'),
                        fatality_details: formData.get('fatality_details'),
                        details_of_investigation: formData.get('details_of_investigation'),
                        disposition: formData.get('disposition'),
                        noted_by_station_officer_id: formData.get('noted_by_station_officer_id'),
                        approved_by_station_officer_id: formData.get('approved_by_station_officer_id'),
                        noted_by_name: spotForm.querySelector('select[name="noted_by_station_officer_id"]')?.selectedOptions?.[0]?.textContent?.trim() || '',
                        approved_by_name: spotForm.querySelector('select[name="approved_by_station_officer_id"]')?.selectedOptions?.[0]?.textContent?.trim() || '',
                        has_progress: false,
                    }),
                    appendSpotReportRow
                );

                if (mapped) {
                    reportStore.spot[mapped.id] = mapped;
                    removeOption(spotIncidentSelect, selectedIncidentId);
                    closeReportModal('spot');
                    switchReportTab('spot');
                }
            });

            progressForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const selectedSpotId = progressSpotSelect?.value;
                const selectedLocation = getSelectedOptionText(progressSpotSelect);
                const fireOriginName = progressFireOriginSelect?.options?.[progressFireOriginSelect.selectedIndex]?.textContent?.trim() || 'N/A';
                const mapped = await submitReportForm(
                    progressForm,
                    '/api/progress-investigation',
                    (formData, data) => ({
                        ...data,
                        id: data.progress_investigation_report_id,
                        type: 'progress',
                        spot_investigation_id: formData.get('spot_investigation_id'),
                        incident_location: selectedLocation,
                        investigator_name: currentOfficerName,
                        fire_origin_id: formData.get('fire_origin_id'),
                        fire_origin_name: fireOriginName,
                        fire_cause: formData.get('fire_cause'),
                        responsible_arson: formData.get('responsible_arson'),
                        has_final: false,
                    }),
                    appendProgressReportRow
                );

                if (mapped) {
                    reportStore.progress[mapped.id] = mapped;
                    if (reportStore.spot[selectedSpotId]) {
                        reportStore.spot[selectedSpotId].has_progress = true;
                        replaceRow('spot', reportStore.spot[selectedSpotId]);
                    }
                    removeOption(progressSpotSelect, selectedSpotId);
                    closeReportModal('progress');
                    switchReportTab('progress');
                }
            });

            finalForm?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const selectedProgressId = finalProgressSelect?.value;
                const selectedLocation = getSelectedOptionText(finalProgressSelect);
                const mapped = await submitReportForm(
                    finalForm,
                    '/api/final-investigation',
                    (formData, data) => ({
                        ...data,
                        id: data.final_investigation_report_id,
                        type: 'final',
                        progress_investigation_id: formData.get('progress_investigation_id'),
                        incident_location: selectedLocation,
                        exact_address: formData.get('exact_address'),
                        total_damage: formData.get('total_damage'),
                        created_at: new Date().toISOString(),
                        fire_origin_name: reportStore.progress[selectedProgressId]?.fire_origin_name || 'N/A',
                        fire_cause: reportStore.progress[selectedProgressId]?.fire_cause || 'N/A',
                    }),
                    appendFinalReportRow
                );

                if (mapped) {
                    reportStore.final[mapped.id] = mapped;
                    if (reportStore.progress[selectedProgressId]) {
                        reportStore.progress[selectedProgressId].has_final = true;
                        replaceRow('progress', reportStore.progress[selectedProgressId]);
                    }
                    removeOption(finalProgressSelect, selectedProgressId);
                    closeReportModal('final');
                    switchReportTab('final');
                }
            });
        });
    </script>
    @include('partials.pwa-body')
</body>
</html>
