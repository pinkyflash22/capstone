@extends('barangay.layouts.app')

@php
    $totalHazardsReported = (int) ($portalStats['total_hazards_reported'] ?? 0);
    $highRiskAreas = (int) ($riskAreaCounts['high'] ?? 0);
    $mediumRiskAreas = (int) ($riskAreaCounts['medium'] ?? 0);
    $lowRiskAreas = (int) ($riskAreaCounts['low'] ?? 0);
    $pendingInspections = (int) ($portalStats['pending_inspections'] ?? 0);
    $pendingActions = (int) ($portalStats['open_actions'] ?? 0);
    $criticalActions = (int) ($portalStats['critical_actions'] ?? 0);
    $totalChecklists = max(0, (int) ($portalStats['checklist_total'] ?? 0));
    $completedChecklists = (int) ($portalStats['checklist_completed'] ?? 0);
    $inspectionCompletionRate = $totalChecklists > 0 ? (int) round(($completedChecklists / $totalChecklists) * 100) : 0;
    $recentIncidentCount = (int) ($portalStats['recent_incidents'] ?? 0);
    $previousIncidentCount = (int) ($portalStats['previous_incidents'] ?? 0);
    $openMitigations = (int) ($portalStats['mitigation_open'] ?? 0);
    $riskScoreRaw = min(100, (int) round(
        ($highRiskAreas * 18)
        + ($mediumRiskAreas * 10)
        + ($pendingInspections * 5)
        + ($criticalActions * 7)
        + max(0, $recentIncidentCount - $lowRiskAreas)
    ));
    $predictedRiskPercentage = max(8, $riskScoreRaw ?: ($totalHazardsReported > 0 ? min(100, $totalHazardsReported * 4) : 12));
    $riskLevel = $predictedRiskPercentage >= 70 ? 'High' : ($predictedRiskPercentage >= 40 ? 'Moderate' : 'Low');
    $riskLevelClass = $riskLevel === 'High' ? 'high' : ($riskLevel === 'Moderate' ? 'medium' : 'low');
    $riskTrend = $recentIncidentCount > $previousIncidentCount ? 'Increasing' : ($recentIncidentCount < $previousIncidentCount ? 'Decreasing' : 'Stable');
    $riskTrendIcon = $riskTrend === 'Increasing' ? 'bi-graph-up-arrow' : ($riskTrend === 'Decreasing' ? 'bi-graph-down-arrow' : 'bi-dash-circle');
    $riskTrendClass = $riskTrend === 'Increasing' ? 'high' : ($riskTrend === 'Decreasing' ? 'low' : 'medium');
    $riskAreaTotal = $highRiskAreas + $mediumRiskAreas + $lowRiskAreas;
    $unresolvedHazards = (int) $predictionChecklists->whereIn('status', ['pending', 'in_progress', 'not_complied'])->count();
    $historicalTotal = (int) ($portalStats['historical_total'] ?? 0);
    $recordedReports = (int) ($portalStats['recorded_reports'] ?? 0);
    $mainDriverLabel = $highRiskAreas > 0 ? $highRiskAreas . ' high-risk ' . ($highRiskAreas === 1 ? 'zone' : 'zones') : ($criticalActions > 0 ? $criticalActions . ' priority ' . ($criticalActions === 1 ? 'action' : 'actions') : 'Monitoring');
    $factorCards = collect([
        [
            'value' => $pendingInspections,
            'label' => 'Pending inspection ' . ($pendingInspections === 1 ? 'item' : 'items'),
            'icon' => 'bi-clipboard2-pulse',
        ],
        [
            'value' => $recentIncidentCount,
            'label' => 'Recent fire-related ' . ($recentIncidentCount === 1 ? 'report' : 'reports'),
            'icon' => 'bi-fire',
        ],
    ]);
    $hallMarkerImage = trim((string) ($hall?->marker?->image ?? ''));
    $hallPhotoUrl = null;

    if ($hallMarkerImage !== '') {
        if (preg_match('/^(https?:)?\/\//i', $hallMarkerImage) || str_starts_with($hallMarkerImage, '/storage/')) {
            $hallPhotoUrl = $hallMarkerImage;
        } elseif (str_starts_with($hallMarkerImage, 'public://') || str_starts_with($hallMarkerImage, 'supabase://')) {
            $hallPhotoUrl = \App\Support\MediaPath::url($hallMarkerImage);
        } else {
            $hallPhotoUrl = asset('storage/' . ltrim($hallMarkerImage, '/'));
        }
    }
    $officerUser = auth()->user();
    $officerPhotoUrl = $officerUser?->profile_thumbnail_url ?: $officerUser?->profile_image_url;
    $officerInitials = strtoupper(substr($officerUser?->first_name ?? 'B', 0, 1) . substr($officerUser?->last_name ?? 'R', 0, 1));
    $preparednessScore = max(18, min(100, (int) round(
        (35 * ($inspectionCompletionRate / 100))
        + (25 * (($lowRiskAreas + 1) / max(1, $highRiskAreas + $mediumRiskAreas + $lowRiskAreas + 1)))
        + (20 * ((max(0, 10 - $pendingActions)) / 10))
        + (20 * ((max(0, 10 - $criticalActions)) / 10))
    )));
    $preparednessLabel = $preparednessScore >= 80 ? 'Operationally ready' : ($preparednessScore >= 60 ? 'Needs targeted follow-through' : 'Requires immediate strengthening');
    $preparednessTone = $preparednessScore >= 80 ? 'good' : ($preparednessScore >= 55 ? 'medium' : 'critical');
    $activityTiles = collect([
        [
            'label' => 'Hazards & Risks',
            'value' => $riskAreaTotal,
            'icon' => 'bi-exclamation-triangle',
            'tone' => 'amber',
            'attention' => $unresolvedHazards,
            'attention_label' => 'unresolved risk signal',
        ],
        [
            'label' => 'Fire Reports',
            'value' => max($recordedReports, $totalHazardsReported),
            'icon' => 'bi-fire',
            'tone' => 'incident',
            'attention' => $recentIncidentCount,
            'attention_label' => 'recent fire-related report',
        ],
        [
            'label' => 'Incident History',
            'value' => $historicalTotal,
            'icon' => 'bi-clock-history',
            'tone' => 'blue',
            'attention' => $recentIncidentCount,
            'attention_label' => 'incident in last 30 days',
        ],
        [
            'label' => 'Checklists',
            'value' => $totalChecklists,
            'icon' => 'bi-clipboard2-check',
            'tone' => 'green',
            'attention' => $pendingInspections,
            'attention_label' => 'pending inspection',
        ],
        [
            'label' => 'Mitigation',
            'value' => $openMitigations,
            'icon' => 'bi-shield-check',
            'tone' => 'purple',
            'attention' => $pendingActions,
            'attention_label' => 'open action',
        ],
    ]);
@endphp

@section('active_nav', 'dashboard')
@section('page_title', 'Barangay Dashboard')
@section('page_subtitle', 'Local fire risk and response overview')
@section('hide_hero', '1')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.pyroPwa?.registerPageSnapshot?.({
        key: 'barangay-dashboard',
        label: 'Barangay Dashboard'
    });

    if (!window.pyroPwa?.isOffline?.()) {
        window.pyroPwa?.updatePageSnapshot?.({
            key: 'barangay-dashboard',
            label: 'Barangay Dashboard'
        });
    }
});
</script>
@endpush

@section('content')
    <style>
        .barangay-dashboard-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .barangay-section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .barangay-section-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 1.35rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .barangay-section-heading p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.6;
            max-width: 70ch;
        }

        .risk-status-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.9fr);
            gap: 18px;
        }

        .risk-status-card {
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #fff1f2 100%);
            border-radius: 30px;
            border: 1px solid rgba(245, 158, 11, 0.14);
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.06);
            padding: 26px 28px;
        }

        .risk-status-header,
        .compact-profile-header,
        .map-card-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 12px;
        }

        .risk-status-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .risk-status-label.high {
            background: #fef2f2;
            color: #b91c1c;
        }

        .risk-status-label.medium {
            background: #fff7ed;
            color: #c2410c;
        }

        .risk-status-label.low {
            background: #ecfdf5;
            color: #047857;
        }

        .risk-status-title {
            margin: 14px 0 0;
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .risk-status-copy {
            margin: 10px 0 0;
            color: #475569;
            font-size: 0.96rem;
            line-height: 1.7;
            max-width: 62ch;
        }

        .risk-status-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 22px;
        }

        .risk-status-metric {
            border-radius: 22px;
            padding: 16px 18px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(226, 232, 240, 0.9);
        }

        .risk-status-metric .label {
            color: #64748b;
            font-size: 0.73rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .risk-status-metric .value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 1.45rem;
            font-weight: 900;
        }

        .risk-status-metric .sub {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.82rem;
            line-height: 1.55;
        }

        .trend-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .trend-pill.high {
            background: #fef2f2;
            color: #b91c1c;
        }

        .trend-pill.medium {
            background: #fff7ed;
            color: #c2410c;
        }

        .trend-pill.low {
            background: #ecfdf5;
            color: #047857;
        }

        .factor-stat-grid,
        .next-actions-list {
            margin: 18px 0 0;
            display: grid;
            gap: 12px;
        }

        .factor-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .factor-stat-card {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 82px;
            border-radius: 18px;
            padding: 15px 16px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-left: 4px solid #A5680B;
        }

        .factor-stat-card__icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            background: #FBF0DD;
            color: #A5680B;
            font-size: 1.1rem;
        }

        .factor-stat-card__value {
            display: block;
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
        }

        .factor-stat-card__label {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .next-action-item,
        .attention-item {
            display: flex;
            gap: 12px;
            align-items: start;
            border-radius: 18px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.86);
            border: 1px solid rgba(226, 232, 240, 0.88);
        }

        .next-action-item i,
        .attention-item i {
            color: #ea580c;
            font-size: 1rem;
            margin-top: 2px;
        }

        .next-action-item strong,
        .attention-item strong {
            display: block;
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .next-action-item span,
        .attention-item span {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.83rem;
            line-height: 1.55;
        }

        .risk-side-card {
            display: grid;
            gap: 18px;
        }

        .decision-card,
        .compact-profile-card,
        .preparedness-card,
        .attention-card,
        .hazard-table-card,
        .map-monitoring-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 30px;
            border: 1px solid rgba(226, 232, 240, 0.92);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.05);
            padding: 24px;
        }

        .decision-card h3,
        .compact-profile-card h3,
        .preparedness-card h3,
        .attention-card h3,
        .hazard-table-card h3,
        .map-monitoring-card h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.1rem;
            font-weight: 900;
        }

        .decision-card p,
        .compact-profile-card p,
        .preparedness-card p,
        .attention-card p,
        .hazard-table-card p,
        .map-monitoring-card p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .metric-card--enhanced {
            padding: 22px 22px 20px;
        }

        .metric-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .metric-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #ea580c;
            background: #fff7ed;
            box-shadow: inset 0 0 0 1px rgba(245, 158, 11, 0.14);
        }

        .metric-card-number {
            margin-top: 18px;
            color: #0f172a;
            font-size: clamp(1.95rem, 2.4vw, 2.55rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
        }

        .metric-card-description {
            margin-top: 10px;
            color: #475569;
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .metric-card-foot {
            margin-top: 12px;
            color: #94a3b8;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .attention-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .attention-item {
            flex-direction: column;
            align-items: stretch;
            padding: 18px;
        }

        .attention-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .attention-value {
            color: #0f172a;
            font-size: 1.75rem;
            font-weight: 900;
            line-height: 1;
        }

        .attention-tag {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
        }

        .attention-tag.critical,
        .attention-tag.high {
            background: #fef2f2;
            color: #b91c1c;
        }

        .attention-tag.medium,
        .attention-tag.pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .attention-tag.low {
            background: #ecfdf5;
            color: #047857;
        }

        .map-monitoring-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: 18px;
        }

        .map-meta,
        .map-filter-group,
        .map-legend,
        .preparedness-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .map-meta-chip,
        .map-filter-btn,
        .legend-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 36px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.79rem;
            font-weight: 800;
        }

        .map-meta-chip {
            background: #f8fafc;
            color: #334155;
            border: 1px solid rgba(226, 232, 240, 0.92);
        }

        .map-filter-btn {
            border: 1px solid rgba(226, 232, 240, 0.92);
            background: #ffffff;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .map-filter-btn.is-active,
        .map-filter-btn:hover {
            background: #fff7ed;
            color: #c2410c;
            border-color: rgba(245, 158, 11, 0.2);
        }

        .legend-chip {
            background: #ffffff;
            color: #475569;
            border: 1px solid rgba(226, 232, 240, 0.92);
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }

        .legend-image {
            width: 16px;
            height: 16px;
            object-fit: contain;
            display: inline-block;
            flex-shrink: 0;
        }

        .hazard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .hazard-table th,
        .hazard-table td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(241, 245, 249, 1);
            vertical-align: top;
        }

        .hazard-table th {
            color: #64748b;
            font-size: 0.74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .hazard-table td {
            color: #334155;
            font-size: 0.88rem;
        }

        .hazard-table strong {
            color: #0f172a;
            font-weight: 800;
        }

        .preparedness-score {
            display: flex;
            align-items: end;
            gap: 10px;
            margin-top: 18px;
        }

        .preparedness-score strong {
            color: #0f172a;
            font-size: clamp(2.5rem, 3vw, 3.15rem);
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -0.05em;
        }

        .preparedness-score span {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 800;
            padding-bottom: 8px;
        }

        .preparedness-progress {
            margin-top: 18px;
            width: 100%;
            height: 14px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .preparedness-progress-bar {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #fb923c 0%, #ea580c 50%, #dc2626 100%);
        }

        .preparedness-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .compact-profile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 20px;
        }

        .compact-profile-item {
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 22px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.92);
            padding: 16px;
        }

        .compact-profile-item__body {
            min-width: 0;
            flex: 1;
        }

        .compact-profile-media {
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.95);
            background: #fff7ed;
            color: #c2410c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.74rem;
            line-height: 1;
        }

        .compact-profile-media--square {
            border-radius: 12px;
        }

        .compact-profile-media--circle {
            border-radius: 999px;
        }

        .compact-profile-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .compact-profile-caption {
            display: block;
            margin-top: 4px;
            color: #B7B4AC;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.2;
        }

        .compact-profile-item .label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .compact-profile-item .value {
            margin-top: 8px;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .compact-profile-item .meta {
            margin-top: 6px;
            color: #64748b;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        @media (max-width: 1280px) {
            .risk-status-grid,
            .attention-grid,
            .compact-profile-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .risk-status-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 960px) {
            .risk-status-metrics,
            .attention-grid,
            .compact-profile-grid {
                grid-template-columns: 1fr 1fr;
            }

            .hazard-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }

        @media (max-width: 768px) {
            .risk-status-metrics,
            .factor-stat-grid,
            .attention-grid,
            .compact-profile-grid {
                grid-template-columns: 1fr;
            }

            .risk-status-card,
            .decision-card,
            .compact-profile-card,
            .preparedness-card,
            .attention-card,
            .hazard-table-card,
            .map-monitoring-card {
                padding: 20px;
                border-radius: 24px;
            }

            .barangay-section-heading {
                align-items: start;
                flex-direction: column;
            }
        }

        .dashboard-lede-card,
        .barangay-risk-profile-card,
        .activity-overview-card,
        .map-monitoring-card,
        .hazard-table-card,
        .preparedness-card {
            border: 1px solid #E9E5DE;
            border-radius: 16px;
            box-shadow: none;
            background: #fff;
        }

        .dashboard-lede-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
        }

        .dashboard-lede-card p {
            margin: 0;
            color: #4A4E58;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .dashboard-lede-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .dashboard-lede-links a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 0 11px;
            border: 1px solid #E9E5DE;
            border-radius: 999px;
            color: #4A4E58;
            background: #FAF8F5;
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none;
        }

        .barangay-risk-profile-card {
            padding: 22px;
        }

        .barangay-risk-profile-card__identity {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .identity-icon {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 14px;
            background: #F3EEFB;
            color: #7A4FB8;
            font-size: 1.25rem;
        }

        .barangay-risk-profile-card__identity h2 {
            margin: 0;
            color: #0f172a;
            font-size: clamp(1.35rem, 2vw, 1.85rem);
            font-weight: 900;
            letter-spacing: 0;
        }

        .barangay-risk-profile-card__identity p {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 5px 0 0;
            color: #6B6E76;
            font-size: 0.9rem;
            font-weight: 650;
        }

        .profile-contact-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .profile-contact-tile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            padding: 14px;
            border: 1px solid #E9E5DE;
            border-radius: 14px;
            background: #FAF8F5;
        }

        .compact-profile-item .value,
        .profile-contact-tile .value {
            overflow-wrap: normal;
            word-break: normal;
            white-space: normal;
        }

        .risk-classification-divider {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #E9E5DE;
            color: #6B6E76;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .risk-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .risk-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 34px;
            padding: 0 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 850;
        }

        .risk-pill--high { background: #FBEAE4; color: #B23A22; }
        .risk-pill--medium { background: #FBF0DD; color: #A5680B; }
        .risk-pill--low { background: #E7F5EC; color: #1E7A4C; }
        .risk-pill--trend.high { background: #FBEAE4; color: #B23A22; }
        .risk-pill--trend.medium { background: #FBF0DD; color: #A5680B; }
        .risk-pill--trend.low { background: #E7F5EC; color: #1E7A4C; }
        .risk-pill--neutral { background: #F0EEEA; color: #4A4E58; }

        .activity-overview-card {
            padding: 20px;
        }

        .activity-overview-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .activity-tile {
            min-width: 0;
            padding: 15px;
            border: 1px solid #E9E5DE;
            border-radius: 14px;
            background: #fff;
        }

        .activity-tile__top {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4A4E58;
            font-size: 0.78rem;
            font-weight: 850;
        }

        .activity-tile__icon {
            display: grid;
            width: 30px;
            height: 30px;
            place-items: center;
            border-radius: 10px;
        }

        .activity-tile strong {
            display: block;
            margin-top: 13px;
            color: #0f172a;
            font-size: 1.8rem;
            font-weight: 900;
            line-height: 1;
        }

        .activity-tile__status {
            margin: 9px 0 0;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .activity-tile__status.is-clear { color: #1E7A4C; }
        .activity-tile__status.is-attention { color: #A5680B; }
        .activity-tile--incident .activity-tile__icon { background: #FBEAE4; color: #B23A22; }
        .activity-tile--amber .activity-tile__icon { background: #FBF0DD; color: #A5680B; }
        .activity-tile--green .activity-tile__icon { background: #E7F5EC; color: #1E7A4C; }
        .activity-tile--purple .activity-tile__icon { background: #F3EEFB; color: #7A4FB8; }
        .activity-tile--blue .activity-tile__icon { background: #EAF1FB; color: #3A6FB0; }

        .map-control-group {
            display: grid;
            gap: 8px;
        }

        .map-control-label {
            color: #6B6E76;
            font-size: 0.68rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .map-layer-toggle-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .map-layer-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 0 11px;
            border: 1px solid #E9E5DE;
            border-radius: 999px;
            background: #fff;
            color: #4A4E58;
            font-size: 0.78rem;
            font-weight: 800;
        }

        .map-layer-toggle input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .map-layer-toggle:has(input:not(:checked)) {
            opacity: 0.48;
        }

        .layer-dot,
        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 auto;
        }

        .pin-tag {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 10px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.76rem;
            font-weight: 850;
        }

        .pin-tag--fire { background: #FBEAE4; color: #B23A22; border-color: rgba(178, 58, 34, 0.16); }
        .pin-tag--civic { background: #F3EEFB; color: #7A4FB8; border-color: rgba(122, 79, 184, 0.18); }
        .pin-tag--neutral { background: #F0EEEA; color: #4A4E58; border-color: #E9E5DE; }

        .record-location-title,
        .record-location-tech {
            display: block;
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .record-location-title {
            color: #334155;
            font-weight: 850;
        }

        .record-location-tech {
            margin-top: 4px;
            color: #8A8D96;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 0.7rem;
            line-height: 1.45;
        }

        .preparedness-progress--good .preparedness-progress-bar {
            background: linear-gradient(90deg, #22c55e 0%, #1E7A4C 100%);
        }

        .preparedness-progress--medium .preparedness-progress-bar {
            background: linear-gradient(90deg, #f59e0b 0%, #A5680B 100%);
        }

        .preparedness-progress--critical .preparedness-progress-bar {
            background: linear-gradient(90deg, #ef4444 0%, #B23A22 100%);
        }

        body.barangay-portal-shell .pyro-routing-launcher {
            right: 24px;
            bottom: calc(24px + env(safe-area-inset-bottom, 0px));
            z-index: 1030;
        }

        @media (max-width: 1280px) {
            .activity-overview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 980px) {
            .profile-contact-grid,
            .panel-grid.two {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-lede-card {
                align-items: flex-start;
                flex-direction: column;
            }

            .activity-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-record-row {
            cursor: pointer;
            transition: background-color 0.18s ease;
        }

        .dashboard-record-row:hover,
        .dashboard-record-row:focus-within {
            background: #fff8ef;
        }

        .dashboard-record-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid rgba(217, 119, 6, 0.2);
            border-radius: 10px;
            background: #fff;
            color: #b45309;
        }

        .dashboard-record-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
            color: #64748b;
            font-size: 0.78rem;
        }

        .dashboard-record-pagination__buttons {
            display: flex;
            gap: 6px;
        }

        .dashboard-page-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 32px;
            padding: 0 10px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 10px;
            background: #fff;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .dashboard-page-button:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .dashboard-record-modal[hidden] {
            display: none;
        }

        .dashboard-record-modal {
            position: fixed;
            inset: 0;
            z-index: 5000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(6px);
        }

        .dashboard-record-modal__card {
            display: flex;
            flex-direction: column;
            width: min(440px, 100%);
            max-height: min(78dvh, 560px);
            overflow: hidden;
            border: 1px solid rgba(245, 158, 11, 0.18);
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
        }

        .dashboard-record-modal__header,
        .dashboard-record-modal__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex: 0 0 auto;
            padding: 14px 16px;
        }

        .dashboard-record-modal__header {
            border-bottom: 1px solid #f1f5f9;
        }

        .dashboard-record-modal__header h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
        }

        .dashboard-record-modal__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
        }

        .dashboard-record-modal__body {
            min-height: 0;
            overflow-y: auto;
            padding: 14px 16px;
        }

        .dashboard-record-modal-content {
            display: grid;
            gap: 14px;
        }

        .dashboard-record-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .dashboard-record-badge {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 0 11px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 850;
        }

        .dashboard-record-badge--classification {
            background: #FBF0DD;
            color: #A5680B;
        }

        .dashboard-record-badge--status {
            background: #EAF1FB;
            color: #3A6FB0;
        }

        .dashboard-record-chip-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-record-chip-card {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 76px;
            padding: 12px;
            border: 1px solid #E9E5DE;
            border-radius: 14px;
            background: #fff;
        }

        .dashboard-record-chip-card i {
            display: grid;
            width: 32px;
            height: 32px;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 10px;
            background: #FAF8F5;
            color: #A5680B;
        }

        .dashboard-record-chip-card span,
        .dashboard-record-chip-card strong {
            display: block;
        }

        .dashboard-record-chip-card span {
            color: #8A8D96;
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .dashboard-record-chip-card strong {
            margin-top: 3px;
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 850;
            line-height: 1.35;
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .dashboard-record-section {
            overflow: hidden;
            border: 1px solid #E9E5DE;
            border-radius: 14px;
            background: #fff;
        }

        .dashboard-record-section__header {
            padding: 10px 12px;
            border-bottom: 1px solid #E9E5DE;
            background: #FAF8F5;
            color: #4A4E58;
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .dashboard-record-section__body {
            padding: 12px;
        }

        .dashboard-record-description {
            margin: 0;
            color: #334155;
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .dashboard-record-technical {
            margin-top: 9px;
            padding: 8px 10px;
            border: 1px dashed #E9E5DE;
            border-radius: 10px;
            background: #FAF8F5;
            color: #8A8D96;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 11px;
            line-height: 1.45;
        }

        .dashboard-record-fact-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
        }

        .dashboard-record-fact {
            min-width: 0;
            padding: 12px;
            border-right: 1px solid #E9E5DE;
            border-bottom: 1px solid #E9E5DE;
        }

        .dashboard-record-fact:nth-child(2n) {
            border-right: 0;
        }

        .dashboard-record-fact:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .dashboard-record-fact span,
        .dashboard-record-fact strong {
            display: block;
        }

        .dashboard-record-fact span {
            color: #8A8D96;
            font-size: 0.66rem;
            font-weight: 900;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .dashboard-record-fact strong {
            margin-top: 5px;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 850;
            line-height: 1.4;
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .dashboard-record-empty {
            color: #B7B4AC;
            font-style: italic;
            font-weight: 650;
        }

        .dashboard-record-fact__value--pending {
            color: #A5680B !important;
        }

        .dashboard-record-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-record-detail {
            padding: 10px 12px;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            background: #f8fafc;
        }

        .dashboard-record-detail--wide {
            grid-column: 1 / -1;
        }

        .dashboard-record-detail span,
        .dashboard-record-detail strong {
            display: block;
        }

        .dashboard-record-detail span {
            margin-bottom: 3px;
            color: #94a3b8;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .dashboard-record-detail strong {
            color: #334155;
            font-size: 0.8rem;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .dashboard-record-modal__footer {
            justify-content: flex-end;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        .dashboard-record-modal__footer button {
            min-height: 34px;
            padding: 0 14px;
            border: 1px solid rgba(217, 119, 6, 0.2);
            border-radius: 10px;
            background: #fff7ed;
            color: #b45309;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .barangay-dashboard-marker__pulse {
            position: absolute;
            inset: 2px;
            border: 2px solid rgba(220, 38, 38, 0.72);
            border-radius: 999px;
            animation: barangayMarkerPulse 1.8s ease-out infinite;
        }

        @keyframes barangayMarkerPulse {
            0% { opacity: 0.8; transform: scale(0.9); }
            100% { opacity: 0; transform: scale(1.8); }
        }

        @media (max-width: 640px) {
            .dashboard-record-pagination {
                align-items: flex-start;
                flex-direction: column;
            }

            .dashboard-record-modal {
                padding: 10px;
            }

            .dashboard-record-modal__card {
                max-height: calc(100dvh - 20px);
                border-radius: 16px;
            }

            .dashboard-record-detail-grid {
                grid-template-columns: 1fr;
                gap: 7px;
            }

            .dashboard-record-chip-grid,
            .dashboard-record-fact-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-record-fact,
            .dashboard-record-fact:nth-child(2n),
            .dashboard-record-fact:nth-last-child(-n + 2) {
                border-right: 0;
                border-bottom: 1px solid #E9E5DE;
            }

            .dashboard-record-fact:last-child {
                border-bottom: 0;
            }

            .dashboard-record-detail--wide {
                grid-column: auto;
            }
        }
    </style>

    <div class="barangay-dashboard-stack">
        <section class="dashboard-lede-card">
            <p>Review current risks, incidents, and pending actions for your barangay.</p>
            <div class="dashboard-lede-links" aria-label="Dashboard shortcuts">
                <a href="{{ route('barangay.hazards') }}"><i class="bi bi-exclamation-triangle"></i> Risks</a>
                <a href="#barangayDashboardMap"><i class="bi bi-map"></i> Live map</a>
                <a href="{{ route('barangay.checklists') }}"><i class="bi bi-clipboard-data"></i> Actions</a>
            </div>
        </section>

        <section class="barangay-risk-profile-card">
            <div class="barangay-risk-profile-card__identity">
                <div class="identity-icon" aria-hidden="true">
                    <i class="bi bi-building"></i>
                </div>
                <div>
                    <h2>{{ $barangay?->barangay_name ?? 'Barangay not linked' }}</h2>
                    <p><i class="bi bi-geo-alt"></i> {{ $city?->city_name ?? 'No city data' }}</p>
                </div>
            </div>

            <div class="profile-contact-grid">
                <div class="profile-contact-tile">
                    <div class="compact-profile-media compact-profile-media--square" aria-hidden="true">
                        @if($hallPhotoUrl)
                            <img src="{{ $hallPhotoUrl }}" alt="">
                        @else
                            <i class="bi bi-building"></i>
                        @endif
                    </div>
                    <div class="compact-profile-item__body">
                        <div class="label">Barangay Hall</div>
                        <div class="value">{{ $hall?->hall_name ?? 'Hall profile pending' }}</div>
                        @if($hallPhotoUrl)
                            <span class="compact-profile-caption">Photo on file</span>
                        @endif
                    </div>
                </div>
                <div class="profile-contact-tile">
                    <div class="compact-profile-media compact-profile-media--circle" aria-hidden="true">
                        @if($officerPhotoUrl)
                            <img src="{{ $officerPhotoUrl }}" alt="">
                        @else
                            {{ $officerInitials }}
                        @endif
                    </div>
                    <div class="compact-profile-item__body">
                        <div class="label">Officer-in-charge</div>
                        <div class="value">{{ $officerUser?->full_name }}</div>
                    </div>
                </div>
            </div>

            <div class="risk-classification-divider">Current fire risk classification</div>
            <div class="risk-pill-row">
                <span class="risk-pill risk-pill--{{ $riskLevelClass }}"><i class="bi bi-shield-exclamation"></i> {{ $riskLevel }} fire risk</span>
                <span class="risk-pill risk-pill--trend {{ $riskTrendClass }}"><i class="bi {{ $riskTrendIcon }}"></i> {{ $riskTrend }}</span>
                <span class="risk-pill risk-pill--neutral">{{ $predictedRiskPercentage }}% predicted risk</span>
                <span class="risk-pill risk-pill--neutral">{{ $mainDriverLabel }}</span>
            </div>

            <div class="factor-stat-grid">
                @foreach($factorCards as $factor)
                    <div class="factor-stat-card">
                        <span class="factor-stat-card__icon" aria-hidden="true">
                            <i class="bi {{ $factor['icon'] }}"></i>
                        </span>
                        <div>
                            <strong class="factor-stat-card__value">{{ $factor['value'] }}</strong>
                            <span class="factor-stat-card__label">{{ $factor['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="activity-overview-card">
            <div class="barangay-section-heading">
                <div>
                    <h2>Barangay Activity Overview</h2>
                </div>
            </div>

            <div class="activity-overview-grid">
                @foreach($activityTiles as $tile)
                    <article class="activity-tile activity-tile--{{ $tile['tone'] }}">
                        <div class="activity-tile__top">
                            <span class="activity-tile__icon"><i class="bi {{ $tile['icon'] }}"></i></span>
                            <span>{{ $tile['label'] }}</span>
                        </div>
                        <strong>{{ $tile['value'] }}</strong>
                        @if((int) $tile['attention'] > 0)
                            <p class="activity-tile__status is-attention">{{ $tile['attention'] }} {{ $tile['attention_label'] }}{{ (int) $tile['attention'] === 1 ? '' : 's' }}</p>
                        @else
                            <p class="activity-tile__status is-clear"><i class="bi bi-check2-circle"></i> All clear</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section class="map-monitoring-card">
            <div class="map-card-header">
                <div>
                    <div class="panel-eyebrow">Live Map</div>
                    <h3>Incidents and hazards</h3>
                </div>
                <div class="map-meta">
                    <span class="map-meta-chip"><i class="bi bi-broadcast-pin"></i> Active incidents: <strong id="barangayMapIncidentCount">0</strong></span>
                    <span class="map-meta-chip"><i class="bi bi-clock-history"></i> Last updated: <strong id="barangayMapLastUpdated">{{ now()->format('M d, Y h:i A') }}</strong></span>
                </div>
            </div>

            <div class="map-monitoring-toolbar">
                <div class="map-control-group">
                    <div class="map-control-label">View</div>
                    <div class="map-filter-group" data-map-filter-group>
                        <button type="button" class="map-filter-btn is-active" data-map-filter="all">All</button>
                        <button type="button" class="map-filter-btn" data-map-filter="incidents">Incidents</button>
                        <button type="button" class="map-filter-btn" data-map-filter="stations">Stations</button>
                        <button type="button" class="map-filter-btn" data-map-filter="hazards">Hazards</button>
                    </div>
                </div>
                <div class="map-control-group">
                    <div class="map-control-label">Layers</div>
                    <div class="map-layer-toggle-group">
                        <label class="map-layer-toggle"><input type="checkbox" data-map-layer="incidents" checked><span class="layer-dot" style="background:#dc2626"></span> Incidents</label>
                        <label class="map-layer-toggle"><input type="checkbox" data-map-layer="stations" checked><span class="layer-dot" style="background:#2563eb"></span> Stations</label>
                        <label class="map-layer-toggle"><input type="checkbox" data-map-layer="barangay" checked><span class="layer-dot" style="background:#0f766e"></span> Barangay hall</label>
                        <label class="map-layer-toggle"><input type="checkbox" data-map-layer="hazard" checked><span class="layer-dot" style="background:#d97706"></span> Hazard areas</label>
                        <label class="map-layer-toggle"><input type="checkbox" data-map-layer="support" checked><span class="layer-dot" style="background:#ef4444"></span> Support-hydrants</label>
                    </div>
                </div>
            </div>

            <div class="map-card" style="margin-top:18px;">
                <div
                    id="barangayDashboardMap"
                    style="height:360px;border-radius:22px;overflow:hidden;background:#f8fafc;border:1px solid rgba(226,232,240,0.92);"
                    data-barangay-name="{{ $barangay?->barangay_name ?? '' }}"
                    data-mini-map-pins='@json($miniMapPins ?? [])'
                    data-marker-feed-url="{{ route('map.markers.feed') }}"
                    data-barangay-boundaries='@json($barangayBoundaryCollection ?? [])'
                    data-current-barangay-id="{{ $currentBarangayId ?? '' }}"
                ></div>
                <div class="two-col" style="margin-top:16px;">
                    <div>
                        <div class="tiny-label">Latest Local Pins</div>
                        <div id="barangayDashboardMapLatestPins" class="tag-row">
                            @forelse($miniMapPins as $pin)
                                @php
                                    $pinCategory = strtolower((string) ($pin['category'] ?? ''));
                                    $pinClass = $pinCategory === 'fire' ? 'pin-tag pin-tag--fire' : ($pinCategory === 'barangay' ? 'pin-tag pin-tag--civic' : 'pin-tag pin-tag--neutral');
                                @endphp
                                <span class="{{ $pinClass }}">{{ $pin['title'] }}</span>
                            @empty
                                <span class="tag deferred">No latest pins yet</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="tiny-label">Map Interpretation</div>
                        <div class="tag-row">
                            <span class="legend-chip"><span class="legend-dot" style="background:#dc2626"></span> Fire / incident points</span>
                            <span class="legend-chip"><span class="legend-dot" style="background:#2563eb"></span> Stations</span>
                            <span class="legend-chip"><span class="legend-dot" style="background:#0f766e"></span> Barangay / civic context</span>
                            <span class="legend-chip"><span class="legend-dot" style="background:#d97706"></span> Hazard areas</span>
                            <span class="legend-chip"><span class="legend-dot" style="background:#ef4444"></span> Hydrant / support access</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="panel-grid two">
            <article class="hazard-table-card">
                <div class="panel-eyebrow">Fire Records</div>
                <h3>Recent incidents</h3>

                @if($dashboardRecords->isEmpty())
                    <div class="empty-state" id="dashboardRecordsEmpty">No fire records found.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="hazard-table" id="dashboardRecordsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th aria-label="Actions"></th>
                                </tr>
                            </thead>
                            <tbody id="dashboardRecordsBody">
                                @foreach($dashboardRecords as $record)
                                    <tr class="dashboard-record-row" data-record-id="{{ $record['id'] }}">
                                        <td><strong>{{ $record['date'] }}</strong><br><span class="text-slate-400">{{ $record['time'] }}</span></td>
                                        <td><strong>{{ $record['display_title'] ?? $record['classification'] }}</strong><br><span class="text-slate-400">{{ $record['cause'] }}</span></td>
                                        <td>
                                            <strong class="record-location-title">{{ $record['location_label'] ?? $record['location'] }}</strong>
                                            @if(!empty($record['technical_detail']))
                                                <span class="record-location-tech">{{ $record['technical_detail'] }}</span>
                                            @endif
                                        </td>
                                        <td><span class="tag {{ $record['verified'] ? 'complied' : 'pending' }}">{{ $record['status'] }}</span></td>
                                        <td>
                                            <button type="button" class="dashboard-record-view" data-record-view="{{ $record['id'] }}" aria-label="View fire record">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="dashboard-record-pagination"
                        id="dashboardRecordsPagination"
                        data-endpoint="{{ route('barangay.dashboard.records') }}"
                    >
                        <span id="dashboardRecordsSummary">
                            Showing {{ $dashboardRecords->firstItem() }}–{{ $dashboardRecords->lastItem() }} of {{ $dashboardRecords->total() }}
                        </span>
                        <div class="dashboard-record-pagination__buttons">
                            <button type="button" class="dashboard-page-button" data-record-page="{{ max(1, $dashboardRecords->currentPage() - 1) }}" @disabled($dashboardRecords->onFirstPage())>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button type="button" class="dashboard-page-button" disabled>
                                {{ $dashboardRecords->currentPage() }} / {{ max(1, $dashboardRecords->lastPage()) }}
                            </button>
                            <button type="button" class="dashboard-page-button" data-record-page="{{ min($dashboardRecords->lastPage(), $dashboardRecords->currentPage() + 1) }}" @disabled(! $dashboardRecords->hasMorePages())>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif
            </article>

            <article class="preparedness-card">
                <div class="panel-eyebrow">Community Preparedness Score</div>
                <h3>Readiness snapshot</h3>
                <p>The score combines inspection completion, unresolved action pressure, and the current distribution of low-, medium-, and high-risk areas.</p>

                <div class="preparedness-score">
                    <strong>{{ $preparednessScore }}</strong>
                    <span>/ 100</span>
                </div>

                <div class="preparedness-meta">
                    <span class="preparedness-pill"><i class="bi bi-shield-check"></i> {{ $preparednessLabel }}</span>
                    <span class="map-meta-chip"><i class="bi bi-check2-square"></i> {{ $inspectionCompletionRate }}% checklist completion</span>
                </div>

                <div class="preparedness-progress preparedness-progress--{{ $preparednessTone }}" aria-label="Preparedness score progress">
                    <div class="preparedness-progress-bar" style="width: {{ $preparednessScore }}%;"></div>
                </div>

                <div class="next-actions-list">
                    <div class="next-action-item">
                        <i class="bi bi-info-circle"></i>
                        <div>
                            <strong>How the score is derived</strong>
                            <span>Higher completion rates, fewer open critical actions, and more low-risk areas improve the score. Backlogs and repeated hotspots reduce it.</span>
                        </div>
                    </div>
                    <div class="next-action-item">
                        <i class="bi bi-arrow-repeat"></i>
                        <div>
                            <strong>Immediate next step</strong>
                            <span>{{ $pendingActions > 0 ? 'Close open barangay actions and validate hotspot areas to lift readiness.' : 'Maintain patrol visibility and keep mapping data current to hold readiness.' }}</span>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>

    <div id="dashboardRecordModal" class="dashboard-record-modal" role="dialog" aria-modal="true" aria-labelledby="dashboardRecordModalTitle" hidden>
        <div class="dashboard-record-modal__card">
            <div class="dashboard-record-modal__header">
                <h3 id="dashboardRecordModalTitle">Fire Record</h3>
                <button type="button" class="dashboard-record-modal__close" data-record-modal-close aria-label="Close record details">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="dashboard-record-modal__body" id="dashboardRecordModalBody"></div>
            <div class="dashboard-record-modal__footer">
                <button type="button" data-record-modal-close><i class="bi bi-x-circle"></i> Close</button>
            </div>
        </div>
    </div>

    <script type="application/json" id="dashboardRecordsInitialData">@json($dashboardRecords->items())</script>
@endsection

@push('scripts')
    @vite('resources/js/barangay/dashboard.js')
@endpush
