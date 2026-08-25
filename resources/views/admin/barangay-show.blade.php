<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $barangay->barangay_name }} · Barangay Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f8fafc; color: #1e293b; }
        .page { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .hero, .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
        .hero { padding: 24px; margin-bottom: 20px; }
        .hero h1 { margin: 0 0 8px; font-size: 2rem; }
        .hero p { margin: 0; color: #64748b; }
        .actions { margin-top: 18px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 16px; border-radius: 12px; text-decoration: none; font-weight: 700; }
        .btn.primary { background: linear-gradient(135deg, #c63d26 0%, #d18a16 100%); color: #fff; border: 1px solid rgba(198, 61, 38, 0.14); }
        .btn.secondary { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .stat { padding: 18px; }
        .stat .label { color: #64748b; font-size: 0.85rem; margin-bottom: 8px; }
        .stat .value { font-size: 1.6rem; font-weight: 800; }
        .section { padding: 20px; margin-bottom: 20px; }
        .section h2 { margin: 0 0 14px; font-size: 1.1rem; }
        .details { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
        .detail { background: #f8fafc; border-radius: 14px; padding: 14px; }
        .detail .label { color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .detail .value { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { font-size: 0.8rem; color: #64748b; text-transform: uppercase; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    @include('partials.voice-call-config')
    @include('partials.unified-notifications')
    @php
        $isStationAdminRoute = request()->routeIs('station-admin.*');
        $mapRoute = $isStationAdminRoute ? 'station-admin.maps' : 'admin.maps';
        $backRoute = $isStationAdminRoute ? 'station-admin.resource-management.index' : 'admin.resource-management.index';
        $focusLat = $barangay->hall?->marker?->lat;
        $focusLng = $barangay->hall?->marker?->lng;
    @endphp
    <div class="page">
        <section class="hero">
            <h1>{{ $barangay->barangay_name }}</h1>
            <p>
                {{ $barangay->city?->city_name ?? 'Unknown city' }}
                @if($barangay->city?->province?->province_name)
                    , {{ $barangay->city->province->province_name }}
                @endif
            </p>
            <div class="actions">
                <a
                    class="btn primary"
                    href="{{ route($mapRoute, array_filter([
                        'open_boundary_editor' => 1,
                        'focus_barangay_id' => $barangay->barangay_id,
                        'focus_barangay_name' => $barangay->barangay_name,
                        'focus_city_id' => $barangay->city?->city_id,
                        'focus_city_name' => $barangay->city?->city_name,
                        'focus_lat' => $focusLat,
                        'focus_lng' => $focusLng,
                    ], fn ($value) => $value !== null && $value !== '')) }}"
                >
                    <i class="fa-solid fa-draw-polygon"></i> Open Boundary Editor
                </a>
                <a class="btn secondary" href="{{ route($backRoute) }}">
                    <i class="fa-solid fa-arrow-left"></i> Back to Resource Management
                </a>
            </div>
        </section>

        <section class="grid">
            <div class="card stat"><div class="label">Residents Linked</div><div class="value">{{ $summary['resident_count'] }}</div></div>
            <div class="card stat"><div class="label">Addresses</div><div class="value">{{ $summary['address_count'] }}</div></div>
            <div class="card stat"><div class="label">Risk / Hazard Records</div><div class="value">{{ $summary['risk_area_count'] }}</div></div>
            <div class="card stat"><div class="label">Boundary Records</div><div class="value">{{ $summary['boundary_count'] }}</div></div>
            <div class="card stat"><div class="label">Historical Incidents</div><div class="value">{{ $summary['historical_incident_count'] }}</div></div>
            <div class="card stat"><div class="label">Live Incidents</div><div class="value">{{ $summary['live_incident_count'] }}</div></div>
            <div class="card stat"><div class="label">Covered Stations</div><div class="value">{{ $summary['station_count'] }}</div></div>
        </section>

        <section class="card section">
            <h2>Barangay Information</h2>
            <div class="details">
                <div class="detail"><div class="label">Barangay Hall</div><div class="value">{{ $barangay->hall?->hall_name ?? 'No hall linked yet' }}</div></div>
                <div class="detail"><div class="label">Hall Contact</div><div class="value">{{ $barangay->hall?->contact_number ?? 'Not available' }}</div></div>
                <div class="detail"><div class="label">Hall Email</div><div class="value">{{ $barangay->hall?->email ?? 'Not available' }}</div></div>
                <div class="detail">
                    <div class="label">Hall Coordinates</div>
                    <div class="value">
                        @if($barangay->hall?->marker?->lat !== null && $barangay->hall?->marker?->lng !== null)
                            {{ $barangay->hall->marker->lat }}, {{ $barangay->hall->marker->lng }}
                        @else
                            No coordinates yet
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="card section">
            <h2>Boundary Records</h2>
            @if($barangay->boundaryLocations->isEmpty())
                <p class="muted">No saved boundary records yet.</p>
            @else
                <table>
                    <thead><tr><th>Name</th><th>Source</th><th>Type</th><th>Updated</th></tr></thead>
                    <tbody>
                        @foreach($barangay->boundaryLocations->sortByDesc('updated_at') as $boundary)
                            <tr>
                                <td>{{ $boundary->name }}</td>
                                <td>{{ strtoupper($boundary->source ?? 'manual') }}</td>
                                <td>{{ $boundary->boundary_type ?? 'barangay' }}</td>
                                <td>{{ optional($boundary->updated_at)->diffForHumans() ?? 'Unknown' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="card section">
            <h2>Connected Stations</h2>
            @if($barangay->stations->isEmpty())
                <p class="muted">No station coverage linked yet.</p>
            @else
                <table>
                    <thead><tr><th>Station</th><th>Coverage Type</th><th>Source</th><th>Address</th></tr></thead>
                    <tbody>
                        @foreach($barangay->stations as $station)
                            <tr>
                                <td>{{ $station->station_name }}</td>
                                <td>{{ $station->pivot->coverage_type ?? 'primary' }}</td>
                                <td>{{ $station->pivot->source ?? 'system' }}</td>
                                <td>{{ $station->address?->street ?? 'No street address' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="card section">
            <h2>Recent Risk / Hazard Records</h2>
            @if($recentRiskAreas->isEmpty())
                <p class="muted">No linked risk or hazard records yet.</p>
            @else
                <table>
                    <thead><tr><th>Entry</th><th>Assessment</th><th>Status</th><th>Updated</th></tr></thead>
                    <tbody>
                        @foreach($recentRiskAreas as $riskArea)
                            <tr>
                                <td>{{ ucfirst($riskArea->entry_type ?? 'risk') }}</td>
                                <td>{{ $riskArea->assessment_level ?? $riskArea->condition_label ?? 'Not assessed' }}</td>
                                <td>{{ $riskArea->status ?? $riskArea->review_status ?? 'Unknown' }}</td>
                                <td>{{ optional($riskArea->updated_at)->diffForHumans() ?? 'Unknown' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="card section">
            <h2>Recent Historical Incidents</h2>
            @if($recentHistoricalIncidents->isEmpty())
                <p class="muted">No historical incidents linked yet.</p>
            @else
                <table>
                    <thead><tr><th>Date</th><th>Cause</th><th>Location</th><th>Damage</th></tr></thead>
                    <tbody>
                        @foreach($recentHistoricalIncidents as $historicalIncident)
                            <tr>
                                <td>{{ optional($historicalIncident->incident_date)->format('M d, Y') ?? 'Unknown' }}</td>
                                <td>{{ $historicalIncident->fire_cause ?? 'Unknown' }}</td>
                                <td>{{ $historicalIncident->incident_location ?? 'Unknown' }}</td>
                                <td>
                                    @if($historicalIncident->estimated_damage !== null)
                                        PHP {{ number_format((float) $historicalIncident->estimated_damage, 2) }}
                                    @else
                                        Not recorded
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</body>
</html>
