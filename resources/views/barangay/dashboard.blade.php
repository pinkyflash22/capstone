@extends('barangay.layouts.app')

@php
    $totalHazardsReported = (int) ($portalStats['total_hazards_reported'] ?? 0);
    $highRiskAreas = (int) ($riskAreaCounts['high'] ?? 0);
    $mediumRiskAreas = (int) ($riskAreaCounts['medium'] ?? 0);
    $lowRiskAreas = (int) ($riskAreaCounts['low'] ?? 0);
    $pendingInspections = (int) ($portalStats['pending_inspections'] ?? 0);
    $pendingActions = (int) ($portalStats['open_actions'] ?? 0);
    $criticalActions = (int) ($portalStats['critical_actions'] ?? 0);
    $totalChecklists = max(0, (int) $predictionChecklists->count());
    $completedChecklists = (int) $predictionChecklists->where('status', 'complied')->count();
    $inspectionCompletionRate = $totalChecklists > 0 ? (int) round(($completedChecklists / $totalChecklists) * 100) : 0;
    $recentThirtyDays = now()->subDays(30);
    $previousThirtyDays = now()->subDays(60);
    $recentIncidentCount = (int) $historicalIncidents->filter(fn ($incident) => optional($incident->incident_date)->gte($recentThirtyDays))->count();
    $previousIncidentCount = (int) $historicalIncidents->filter(fn ($incident) => optional($incident->incident_date)->between($previousThirtyDays, $recentThirtyDays))->count();
    $hydrantReferenceCount = (int) collect($markers ?? collect())->where('category', 'hydrant')->count();
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
    $contributingFactors = collect([
        $highRiskAreas > 0 ? $highRiskAreas . ' high-risk zone' . ($highRiskAreas === 1 ? '' : 's') : null,
        $pendingInspections > 0 ? $pendingInspections . ' pending inspection item' . ($pendingInspections === 1 ? '' : 's') : null,
        $criticalActions > 0 ? $criticalActions . ' high-priority action' . ($criticalActions === 1 ? '' : 's') : null,
        $recentIncidentCount > 0 ? $recentIncidentCount . ' recent fire-related report' . ($recentIncidentCount === 1 ? '' : 's') : null,
        $hydrantReferenceCount > 0 ? $hydrantReferenceCount . ' hydrant reference point' . ($hydrantReferenceCount === 1 ? '' : 's') . ' to validate' : null,
    ])->filter()->take(4)->values();
    $preparednessScore = max(18, min(100, (int) round(
        (35 * ($inspectionCompletionRate / 100))
        + (25 * (($lowRiskAreas + 1) / max(1, $highRiskAreas + $mediumRiskAreas + $lowRiskAreas + 1)))
        + (20 * ((max(0, 10 - $pendingActions)) / 10))
        + (20 * ((max(0, 10 - $criticalActions)) / 10))
    )));
    $preparednessLabel = $preparednessScore >= 80 ? 'Operationally ready' : ($preparednessScore >= 60 ? 'Needs targeted follow-through' : 'Requires immediate strengthening');
    $highPriorityReports = $predictionChecklists->whereIn('priority_level', ['critical', 'high'])->take(4);
    $attentionItems = collect([
        [
            'label' => 'Pending inspections',
            'value' => $pendingInspections,
            'icon' => 'bi-clipboard2-pulse',
            'class' => 'pending',
            'copy' => 'Inspection and checklist items still waiting for validation.',
        ],
        [
            'label' => 'Unresolved hazards',
            'value' => (int) $predictionChecklists->whereIn('status', ['pending', 'in_progress', 'not_complied'])->count(),
            'icon' => 'bi-exclamation-octagon',
            'class' => 'high',
            'copy' => 'Barangay risk conditions that remain open or not complied with.',
        ],
        [
            'label' => 'Hydrant issues',
            'value' => $hydrantReferenceCount,
            'icon' => 'bi-droplet-half',
            'class' => 'medium',
            'copy' => 'Hydrant and water-access reference points that should be verified onsite.',
        ],
        [
            'label' => 'High-priority reports',
            'value' => $criticalActions,
            'icon' => 'bi-bell-fill',
            'class' => 'critical',
            'copy' => 'Checklist actions flagged as critical or high priority.',
        ],
    ]);
@endphp

@section('active_nav', 'dashboard')
@section('page_title', 'Barangay Dashboard')
@section('page_subtitle', 'Fire risk monitoring, preparedness status, and live community response signals')
@section('hero_title', 'Monitor barangay fire exposure before incidents escalate.')
@section('hero_copy', 'Use this dashboard to detect active risk pressure, prioritize unresolved preparedness work, and act on the latest mapped fire-related signals across your barangay.')

@section('hero_badges')
    <span class="hero-badge"><i class="bi bi-fire"></i> Fire risk monitoring</span>
    <span class="hero-badge"><i class="bi bi-map"></i> GIS incident visibility</span>
    <span class="hero-badge"><i class="bi bi-clipboard-data"></i> Preparedness action tracking</span>
@endsection

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

        .factor-list,
        .next-actions-list {
            margin: 18px 0 0;
            display: grid;
            gap: 12px;
        }

        .factor-item,
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

        .factor-item i,
        .next-action-item i,
        .attention-item i {
            color: #ea580c;
            font-size: 1rem;
            margin-top: 2px;
        }

        .factor-item strong,
        .next-action-item strong,
        .attention-item strong {
            display: block;
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .factor-item span,
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
            border-radius: 22px;
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.92);
            padding: 16px;
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
    </style>

    <div class="barangay-dashboard-stack">
        <section class="risk-status-grid">
            <article class="risk-status-card">
                <div class="risk-status-header">
                    <div>
                        <span class="risk-status-label {{ $riskLevelClass }}">
                            <i class="bi bi-shield-exclamation"></i>
                            Current Barangay Risk Status
                        </span>
                        <h2 class="risk-status-title">{{ $riskLevel }} fire risk posture across the barangay</h2>
                        <p class="risk-status-copy">
                            The current forecast combines mapped high-risk locations, unresolved inspections, recent fire-related reports, and high-priority preparedness actions already tracked in PyroSense.
                        </p>
                    </div>
                    <span class="trend-pill {{ $riskTrendClass }}">
                        <i class="bi {{ $riskTrendIcon }}"></i>
                        {{ $riskTrend }}
                    </span>
                </div>

                <div class="risk-status-metrics">
                    <div class="risk-status-metric">
                        <div class="label">Risk Level</div>
                        <div class="value">{{ $riskLevel }}</div>
                        <div class="sub">Community risk posture based on active local conditions.</div>
                    </div>
                    <div class="risk-status-metric">
                        <div class="label">Predicted Risk</div>
                        <div class="value">{{ $predictedRiskPercentage }}%</div>
                        <div class="sub">Estimated fire-risk probability from current barangay indicators.</div>
                    </div>
                    <div class="risk-status-metric">
                        <div class="label">Main Driver</div>
                        <div class="value">{{ $highRiskAreas > 0 ? $highRiskAreas . ' zones' : 'Monitoring' }}</div>
                        <div class="sub">{{ $criticalActions > 0 ? $criticalActions . ' high-priority action items remain open.' : 'No critical queue detected right now.' }}</div>
                    </div>
                </div>

                <div class="factor-list">
                    @forelse($contributingFactors as $factor)
                        <div class="factor-item">
                            <i class="bi bi-dot"></i>
                            <div>
                                <strong>Contributing factor</strong>
                                <span>{{ $factor }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="factor-item">
                            <i class="bi bi-check2-circle"></i>
                            <div>
                                <strong>No major risk signal detected</strong>
                                <span>Current barangay data does not show strong fire-risk escalation at this moment.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </article>

            <div class="risk-side-card">
                <article class="decision-card">
                    <h3>What should the barangay do next?</h3>
                    <p>These recommendations are based on current hazard density, inspection backlog, and high-priority response items.</p>

                    <div class="next-actions-list">
                        <div class="next-action-item">
                            <i class="bi bi-clipboard-check"></i>
                            <div>
                                <strong>Close pending inspections first</strong>
                                <span>{{ $pendingInspections > 0 ? 'Address the ' . $pendingInspections . ' pending inspection and checklist items before risk pressure grows further.' : 'Inspection backlog is currently under control.' }}</span>
                            </div>
                        </div>
                        <div class="next-action-item">
                            <i class="bi bi-geo-alt"></i>
                            <div>
                                <strong>Focus on recurrent hotspots</strong>
                                <span>{{ $highRiskAreas > 0 ? 'Prioritize site visits and community reminders in high-risk areas that show repeated incident history.' : 'Continue validating emerging locations through the live map and recent reports.' }}</span>
                            </div>
                        </div>
                        <div class="next-action-item">
                            <i class="bi bi-megaphone"></i>
                            <div>
                                <strong>Push preparedness reminders</strong>
                                <span>Use community advisories to reinforce safe wiring, open access routes, and rapid reporting where hazards remain unresolved.</span>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="compact-profile-card">
                    <div class="compact-profile-header">
                        <div>
                            <h3>Barangay Information</h3>
                            <p>Compact reference details for coordination and record validation.</p>
                        </div>
                        <span class="tag low"><i class="bi bi-buildings"></i> Reference</span>
                    </div>

                    <div class="compact-profile-grid">
                        <div class="compact-profile-item">
                            <div class="label">Barangay</div>
                            <div class="value">{{ $barangay?->barangay_name ?? 'Not linked' }}</div>
                            <div class="meta">Primary local jurisdiction in the dashboard context.</div>
                        </div>
                        <div class="compact-profile-item">
                            <div class="label">City</div>
                            <div class="value">{{ $city?->city_name ?? 'No city data' }}</div>
                            <div class="meta">LGU grouping used for risk and address references.</div>
                        </div>
                        <div class="compact-profile-item">
                            <div class="label">Barangay Hall</div>
                            <div class="value">{{ $hall?->hall_name ?? 'Hall profile pending' }}</div>
                            <div class="meta">Coordination point for field reporting and validation.</div>
                        </div>
                        <div class="compact-profile-item">
                            <div class="label">Officer</div>
                            <div class="value">{{ auth()->user()->full_name }}</div>
                            <div class="meta">Current authenticated barangay account holder.</div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section>
            <div class="barangay-section-heading">
                <div>
                    <h2>Operational Metrics</h2>
                    <p>Quick performance indicators showing where the barangay stands on hazards, response pressure, and inspection follow-through.</p>
                </div>
            </div>

            <section class="metric-grid">
                <article class="metric-card metric-card--enhanced">
                    <div class="metric-card-head">
                        <div class="metric-label">Total Hazards Reported</div>
                        <span class="metric-card-icon"><i class="bi bi-fire"></i></span>
                    </div>
                    <div class="metric-card-number">{{ $totalHazardsReported }}</div>
                    <div class="metric-card-description">Historical fire-related hazard reports currently linked to this barangay context.</div>
                    <div class="metric-card-foot">Tracks accumulated exposure and recurrence over time.</div>
                </article>

                <article class="metric-card metric-card--enhanced">
                    <div class="metric-card-head">
                        <div class="metric-label">High-Risk Areas</div>
                        <span class="metric-card-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    </div>
                    <div class="metric-card-number">{{ $highRiskAreas }}</div>
                    <div class="metric-card-description">Locations with repeated fire signals or concentrated unresolved risk conditions.</div>
                    <div class="metric-card-foot">Use these zones to focus patrols, reminders, and rapid validation.</div>
                </article>

                <article class="metric-card metric-card--enhanced">
                    <div class="metric-card-head">
                        <div class="metric-label">Pending Actions</div>
                        <span class="metric-card-icon"><i class="bi bi-list-task"></i></span>
                    </div>
                    <div class="metric-card-number">{{ $pendingActions }}</div>
                    <div class="metric-card-description">Open response, mitigation, and inspection items still waiting for barangay follow-through.</div>
                    <div class="metric-card-foot">Includes pending and in-progress operational tasks.</div>
                </article>

                <article class="metric-card metric-card--enhanced">
                    <div class="metric-card-head">
                        <div class="metric-label">Inspection Completion Rate</div>
                        <span class="metric-card-icon"><i class="bi bi-clipboard2-check"></i></span>
                    </div>
                    <div class="metric-card-number">{{ $inspectionCompletionRate }}%</div>
                    <div class="metric-card-description">Percent of checklist items already marked complied out of all barangay prediction checklist entries.</div>
                    <div class="metric-card-foot">{{ $completedChecklists }} of {{ $totalChecklists }} checklist items completed.</div>
                </article>
            </section>
        </section>

        <section class="map-monitoring-card">
            <div class="map-card-header">
                <div>
                    <div class="panel-eyebrow">Live Incident Monitoring</div>
                    <h3>Map-based incident and hazard awareness</h3>
                    <p>Live barangay map for incidents, stations, and hazards. Existing Leaflet marker interactions and GIS behavior remain active.</p>
                </div>
                <div class="map-meta">
                    <span class="map-meta-chip"><i class="bi bi-broadcast-pin"></i> Active incidents: <strong id="barangayMapIncidentCount">0</strong></span>
                    <span class="map-meta-chip"><i class="bi bi-clock-history"></i> Last updated: <strong id="barangayMapLastUpdated">{{ now()->format('M d, Y h:i A') }}</strong></span>
                </div>
            </div>

            <div class="map-monitoring-toolbar">
                <div class="map-filter-group" data-map-filter-group>
                    <button type="button" class="map-filter-btn is-active" data-map-filter="all">All</button>
                    <button type="button" class="map-filter-btn" data-map-filter="incidents">Incidents</button>
                    <button type="button" class="map-filter-btn" data-map-filter="stations">Stations</button>
                    <button type="button" class="map-filter-btn" data-map-filter="hazards">Hazards</button>
                </div>
                <div class="map-legend">
                    <span class="legend-chip"><img class="legend-image" src="{{ asset('fire.png') }}" alt="Incidents" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';"><span class="legend-dot" style="background:#dc2626;display:none;"></span> Incidents</span>
                    <span class="legend-chip"><img class="legend-image" src="{{ asset('station.png') }}" alt="Stations" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';"><span class="legend-dot" style="background:#2563eb;display:none;"></span> Stations</span>
                    <span class="legend-chip"><img class="legend-image" src="{{ asset('hall.png') }}" alt="Barangay hall" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';"><span class="legend-dot" style="background:#0f766e;display:none;"></span> Barangay hall</span>
                    <span class="legend-chip"><img class="legend-image" src="{{ asset('hazard.png') }}" alt="Hazard areas" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';"><span class="legend-dot" style="background:#b91c1c;display:none;"></span> Hazard areas</span>
                    <span class="legend-chip"><img class="legend-image" src="{{ asset('hydrant.png') }}" alt="Support and hydrants" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-block';"><span class="legend-dot" style="background:#0f766e;display:none;"></span> Support / hydrants</span>
                </div>
            </div>

            <div class="map-card" style="margin-top:18px;">
                <div
                    id="barangayDashboardMap"
                    style="height:360px;border-radius:22px;overflow:hidden;background:#f8fafc;border:1px solid rgba(226,232,240,0.92);"
                    data-barangay-name="{{ $barangay?->barangay_name ?? '' }}"
                    data-mini-map-pins='@json($miniMapPins ?? [])'
                ></div>
                <div class="two-col" style="margin-top:16px;">
                    <div>
                        <div class="tiny-label">Latest Local Pins</div>
                        <div id="barangayDashboardMapLatestPins" class="tag-row">
                            @forelse($miniMapPins as $pin)
                                <span class="tag low">{{ $pin['title'] }}</span>
                            @empty
                                <span class="tag deferred">No latest pins yet</span>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <div class="tiny-label">Map Interpretation</div>
                        <div class="tag-row">
                            <span class="tag high"><i class="bi bi-fire"></i> Fire / incident points</span>
                            <span class="tag medium"><i class="bi bi-geo"></i> Barangay / civic context</span>
                            <span class="tag low"><i class="bi bi-water"></i> Hydrant / support access</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="attention-card">
            <div class="barangay-section-heading">
                <div>
                    <h2>Requires Attention</h2>
                    <p>Immediate items that barangay officials should review to reduce risk exposure and improve response readiness.</p>
                </div>
            </div>

            <div class="attention-grid">
                @foreach($attentionItems as $item)
                    <div class="attention-item">
                        <div class="attention-top">
                            <span class="attention-tag {{ $item['class'] }}"><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</span>
                            <span class="attention-value">{{ $item['value'] }}</span>
                        </div>
                        <div>
                            <strong>{{ $item['label'] }}</strong>
                            <span>{{ $item['copy'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($highPriorityReports->isNotEmpty())
                <div class="list-stack">
                    @foreach($highPriorityReports as $item)
                        <div class="list-item">
                            <h4>{{ $item->title }}</h4>
                            <p>{{ $item->planning_notes ?: $item->ai_output ?: 'No additional operational note available.' }}</p>
                            <div class="tag-row">
                                <span class="tag {{ $item->priority_level }}"><i class="bi bi-flag"></i> {{ strtoupper($item->priority_level) }}</span>
                                <span class="tag {{ $item->status }}"><i class="bi bi-hourglass-split"></i> {{ str_replace('_', ' ', strtoupper($item->status)) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel-grid two">
            <article class="hazard-table-card">
                <div class="panel-eyebrow">Recent Hazard Reports</div>
                <h3>Latest barangay fire-related reports</h3>
                <p>Structured view of the most recent hazard history already linked to the barangay for quick review and prioritization.</p>

                @if($historicalIncidents->isEmpty())
                    <div class="empty-state">No hazard reports are linked to this barangay yet.</div>
                @else
                    <table class="hazard-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hazard Type</th>
                                <th>Location</th>
                                <th>Risk Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalIncidents->take(8) as $incident)
                                @php
                                    $incidentRiskLevel = max(1, (int) ($incident->incident_count ?? 1)) >= 3 ? 'High' : (max(1, (int) ($incident->incident_count ?? 1)) >= 2 ? 'Moderate' : 'Low');
                                    $incidentRiskClass = $incidentRiskLevel === 'High' ? 'high' : ($incidentRiskLevel === 'Moderate' ? 'medium' : 'low');
                                    $incidentStatus = $incident->is_verified ? 'Verified' : 'Pending review';
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ optional($incident->incident_date)->format('M d, Y') ?? 'No date' }}</strong><br>
                                        <span class="text-slate-400">{{ optional($incident->incident_date)->format('h:i A') ?? 'Time not set' }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ ucfirst($incident->fire_classification ?? 'Undetermined') }}</strong><br>
                                        <span class="text-slate-400">{{ $incident->fire_cause ?: 'Cause pending validation' }}</span>
                                    </td>
                                    <td>{{ $incident->incident_location ?: 'Unspecified area' }}</td>
                                    <td><span class="tag {{ $incidentRiskClass }}">{{ $incidentRiskLevel }}</span></td>
                                    <td><span class="tag {{ $incident->is_verified ? 'complied' : 'pending' }}">{{ $incidentStatus }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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

                <div class="preparedness-progress" aria-label="Preparedness score progress">
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
@endsection

@push('scripts')
    @vite('resources/js/barangay/dashboard.js')
@endpush
