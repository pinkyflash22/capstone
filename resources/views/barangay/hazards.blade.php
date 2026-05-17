@extends('barangay.layouts.app')

@section('active_nav', 'hazards')
@section('page_title', 'Hazards')
@section('page_subtitle', 'Hazard concentration, recurring causes, and recent local records')
@section('hero_title', 'Track the hazard patterns that keep repeating in the barangay.')
@section('hero_copy', 'Recurring classifications and causes provide the clearest signal for where public awareness, inspections, and mitigation should tighten first.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-triangle-exclamation"></i> Hazard watch</span>
    <span class="hero-badge"><i class="fa-solid fa-repeat"></i> Repeating causes</span>
    <span class="hero-badge"><i class="fa-solid fa-binoculars"></i> Patrol targeting</span>
@endsection

@section('content')
    <section class="panel-grid three">
        @foreach($hazardMix as $label => $count)
            <article class="panel-card">
                <div class="panel-eyebrow">Hazard Mix</div>
                <h3 class="panel-title">{{ $label }}</h3>
                <p class="panel-copy">{{ $count }} recorded incident(s) in the current local dataset.</p>
            </article>
        @endforeach
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Recent Records</div>
            <h3 class="panel-title">Latest hazard entries</h3>
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

        <article class="panel-card">
            <div class="panel-eyebrow">Checklist Pressure</div>
            <h3 class="panel-title">Open hazard mitigation tasks</h3>
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
@endsection
