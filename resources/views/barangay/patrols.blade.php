@extends('barangay.layouts.app')

@section('active_nav', 'patrols')
@section('page_title', 'Patrol & Inspections')
@section('page_subtitle', 'Inspection tasks, compliance status, and barangay field records')
@section('hero_title', 'Send patrols with a clear list of what must be checked on the ground.')
@section('hero_copy', 'Use open checklist items and the latest barangay hall records to structure inspections around the highest-risk conditions instead of broad untargeted rounds.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-clipboard-check"></i> Inspection planning</span>
    <span class="hero-badge"><i class="fa-solid fa-shoe-prints"></i> Patrol direction</span>
    <span class="hero-badge"><i class="fa-solid fa-building-shield"></i> Compliance follow-through</span>
@endsection

@section('content')
    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Open Tasks</div>
            <h3 class="panel-title">Checklist items for patrol teams</h3>
            @if($predictionChecklists->isEmpty())
                <div class="empty-state">No patrol or inspection checklist items have been generated yet.</div>
            @else
                <div class="list-stack">
                    @foreach($predictionChecklists->whereIn('status', ['pending', 'in_progress'])->take(8) as $item)
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

        <article class="panel-card">
            <div class="panel-eyebrow">Field Records</div>
            <h3 class="panel-title">Barangay hall incident entries</h3>
            @if($fireRecords->isEmpty())
                <div class="empty-state">No barangay hall fire records are attached to this area yet.</div>
            @else
                <div class="table-wrap">
                    <table class="mobile-card-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Classification</th>
                                <th>Cause</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fireRecords->take(8) as $record)
                                <tr>
                                    <td data-label="Date">{{ optional($record->incident_date)->format('M d, Y') }}</td>
                                    <td data-label="Classification">{{ $record->fire_classification ?? 'N/A' }}</td>
                                    <td data-label="Cause">{{ $record->fire_cause ?? 'Pending' }}</td>
                                    <td data-label="Notes" data-mobile-wide>{{ $record->notes ?? 'No field note' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
@endsection
