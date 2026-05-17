@extends('barangay.layouts.app')

@section('active_nav', 'dashboard')
@section('page_title', 'Barangay Dashboard')
@section('page_subtitle', 'Hazard counts, latest reports, and unresolved local risk items')
@section('hero_title', 'See the barangay risk picture before it becomes an incident.')
@section('hero_copy', 'This dashboard now surfaces hazard totals, mapped pins, unresolved inspection pressure, and recent local reports using the existing barangay fire history, checklist, and marker records already in the system.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-shield-heart"></i> Community safety overview</span>
    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> Latest hazard pins</span>
    <span class="hero-badge"><i class="fa-solid fa-list-check"></i> Unresolved local actions</span>
@endsection

@section('content')
    <section class="metric-grid">
        <article class="metric-card">
            <div class="metric-label">Total Hazards Reported</div>
            <div class="metric-value">{{ $portalStats['total_hazards_reported'] }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">High-Risk Areas</div>
            <div class="metric-value">{{ $riskAreaCounts['high'] }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Medium Risk Areas</div>
            <div class="metric-value">{{ $riskAreaCounts['medium'] }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Low Risk Areas</div>
            <div class="metric-value">{{ $riskAreaCounts['low'] }}</div>
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Recent Hazard Reports</div>
            <h3 class="panel-title">Latest barangay hazard records</h3>
            <p class="panel-copy">Pulled from existing historical fire incident entries linked to this barangay.</p>

            @if($historicalIncidents->isEmpty())
                <div class="empty-state">No hazard reports are linked to this barangay yet.</div>
            @else
                <div class="list-stack">
                    @foreach($historicalIncidents->take(6) as $incident)
                        <div class="list-item">
                            <h4>{{ ucfirst($incident->fire_classification ?? 'undetermined') }} hazard</h4>
                            <p>{{ optional($incident->incident_date)->format('F d, Y') }} · {{ $incident->incident_location ?: 'Unspecified area' }}</p>
                            <div class="tag-row">
                                <span class="tag medium">{{ $incident->fire_cause ?: 'Cause pending' }}</span>
                                <span class="tag low">{{ max(1, (int) ($incident->incident_count ?? 1)) }} reported</span>
                                @if($incident->is_verified)
                                    <span class="tag complied">Verified</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Pending / Unresolved</div>
            <h3 class="panel-title">Inspections and unresolved hazards</h3>
            <p class="panel-copy">These counts come from barangay prediction checklist items that are still pending, in progress, or not complied with.</p>

            <div class="list-stack">
                <div class="stat-line">
                    <strong>Pending inspections / unresolved hazards</strong>
                    <span class="tag pending">{{ $portalStats['pending_inspections'] }}</span>
                </div>
                <div class="stat-line">
                    <strong>Critical and high-priority items</strong>
                    <span class="tag high">{{ $portalStats['critical_actions'] }}</span>
                </div>
                <div class="stat-line">
                    <strong>Open active actions</strong>
                    <span class="tag in_progress">{{ $portalStats['open_actions'] }}</span>
                </div>
            </div>

            @if($predictionChecklists->whereIn('status', ['pending', 'in_progress', 'not_complied'])->isNotEmpty())
                <div class="list-stack">
                    @foreach($predictionChecklists->whereIn('status', ['pending', 'in_progress', 'not_complied'])->take(4) as $item)
                        <div class="list-item">
                            <h4>{{ $item->title }}</h4>
                            <p>{{ $item->planning_notes ?: $item->ai_output }}</p>
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
            <div class="panel-eyebrow">Mini Map</div>
            <h3 class="panel-title">Latest hazard pins</h3>
            <p class="panel-copy">Leaflet map view styled to match the system map context and rendering all available markers with the newest local hazard pins highlighted.</p>

            <div class="map-card">
                <div
                    id="barangayDashboardMap"
                    style="height:360px;border-radius:22px;overflow:hidden;background:#f8fafc;border:1px solid rgba(226,232,240,0.92);"
                    data-barangay-name="{{ $barangay?->barangay_name ?? '' }}"
                    data-mini-map-pins='@json($miniMapPins ?? [])'
                ></div>
                <div class="two-col" style="margin-top:16px;">
                    <div>
                        <div class="tiny-label">Map Legend</div>
                        <div class="tag-row">
                            <span class="tag high">Fire</span>
                            <span class="tag medium">Barangay</span>
                            <span class="tag low">Station / Support</span>
                        </div>
                    </div>
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
                </div>
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Risk Bands</div>
            <h3 class="panel-title">Area risk count summary</h3>
            <p class="panel-copy">Risk counts are inferred from repeated hazard reports per incident location in the historical barangay dataset.</p>

            <div class="list-stack">
                <div class="stat-line">
                    <strong>High-risk areas</strong>
                    <span class="tag high">{{ $riskAreaCounts['high'] }}</span>
                </div>
                <div class="stat-line">
                    <strong>Medium-risk areas</strong>
                    <span class="tag medium">{{ $riskAreaCounts['medium'] }}</span>
                </div>
                <div class="stat-line">
                    <strong>Low-risk areas</strong>
                    <span class="tag low">{{ $riskAreaCounts['low'] }}</span>
                </div>
            </div>

            @if($fireRecords->isNotEmpty())
                <div class="list-stack">
                    @foreach($fireRecords->take(3) as $record)
                        <div class="list-item">
                            <h4>{{ ucfirst($record->fire_classification ?? 'undetermined') }} field record</h4>
                            <p>{{ optional($record->incident_date)->format('F d, Y') }} · {{ $record->notes ?: 'No additional field note.' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/barangay/dashboard.js')
@endpush
