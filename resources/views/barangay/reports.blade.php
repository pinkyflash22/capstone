@extends('barangay.layouts.app')

@section('active_nav', 'reports')
@section('page_title', 'Reports & Analytics')
@section('page_subtitle', 'Structured reporting view for local risk, actions, and fire history')
@section('hero_title', 'Summarize the barangay record without leaving the operational context.')
@section('hero_copy', 'This reporting page packages the same data used in day-to-day coordination into an easier reading format for barangay leadership, meetings, and follow-up planning.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-file-lines"></i> Report-ready summaries</span>
    <span class="hero-badge"><i class="fa-solid fa-chart-pie"></i> Risk analytics</span>
    <span class="hero-badge"><i class="fa-solid fa-clock-rotate-left"></i> Historical review</span>
@endsection

@section('content')
    <section class="metric-grid">
        <article class="metric-card">
            <div class="metric-label">Hazard Classes</div>
            <div class="metric-value">{{ count(array_filter($hazardMix)) }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Checklist Statuses</div>
            <div class="metric-value">{{ count(array_filter($statusMix)) }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Recent Posts</div>
            <div class="metric-value">{{ $latestPosts->count() }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Mapped Points</div>
            <div class="metric-value">{{ $markers->count() }}</div>
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Incident Register</div>
            <h3 class="panel-title">Historical incident snapshot</h3>
            @if($historicalIncidents->isEmpty())
                <div class="empty-state">No historical incidents are available for reporting yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Cause</th>
                                <th>Damage</th>
                                <th>Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalIncidents->take(8) as $incident)
                                <tr>
                                    <td>{{ optional($incident->incident_date)->format('M d, Y') }}</td>
                                    <td>{{ $incident->fire_cause ?? 'Pending' }}</td>
                                    <td>{{ $incident->estimated_damage ? number_format((float) $incident->estimated_damage, 2) : 'N/A' }}</td>
                                    <td>{{ $incident->is_verified ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Action Register</div>
            <h3 class="panel-title">Checklist and mitigation reporting</h3>
            @if($predictionChecklists->isEmpty())
                <div class="empty-state">No checklist analytics are available for this barangay yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($predictionChecklists->take(8) as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ strtoupper($item->priority_level) }}</td>
                                    <td>{{ str_replace('_', ' ', strtoupper($item->status)) }}</td>
                                    <td>{{ optional($item->target_date)->format('M d, Y') ?? 'No target' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
@endsection
