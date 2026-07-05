@extends('firefighters.v2-layout', [
    'active' => 'station',
    'pageTitle' => 'Firefighter Station V2',
    'pageSubtitle' => 'Station profile, crew, resources, and reports in one cleaner firefighter workspace'
])

@section('title', 'PyroSense · Firefighter Station V2')

@section('content')
    <section class="ff2-page-header">
        <span class="ff2-page-header__eyebrow"><i class="fa-solid fa-building-shield"></i> Station Intelligence</span>
        <h2>{{ $station->station_name ?? 'Assigned Station' }}</h2>
        <p>This page reuses your station, team, equipment, and investigation report data while condensing the layout into a cleaner station operations overview.</p>
    </section>

    <section class="ff2-stats">
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Station Crew</span><i class="fa-solid fa-user-group text-[#c94b2a]"></i></div><div class="ff2-stat__value">{{ $stationOfficers->count() }}</div><div class="ff2-stat__meta">Personnel currently tied to this station.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Team Members</span><i class="fa-solid fa-people-group text-[#db9218]"></i></div><div class="ff2-stat__value">{{ $crewMembers->count() }}</div><div class="ff2-stat__meta">Your active team composition.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Equipment</span><i class="fa-solid fa-toolbox text-[#124e5d]"></i></div><div class="ff2-stat__value">{{ $equipmentAssignments->count() }}</div><div class="ff2-stat__meta">Assigned station equipment records.</div></article>
        <article class="ff2-stat"><div class="ff2-stat__label"><span>Reports</span><i class="fa-solid fa-file-lines text-[#1d9b60]"></i></div><div class="ff2-stat__value">{{ $spotReports->total() + $progressReports->total() + $finalReports->total() }}</div><div class="ff2-stat__meta">Spot, progress, and final reports combined.</div></article>
    </section>

    <section class="ff2-grid-2">
        <article class="ff2-card">
            <div class="ff2-card__body">
                <h3 class="ff2-card__title">Crew Roster</h3>
                <p class="ff2-card__subtitle">Pulled from the same team and station officer records used by the current firefighter module.</p>
                <div class="ff2-list" style="margin-top:18px;">
                    @forelse($crewMembers as $member)
                        <div class="ff2-list-item">
                            <div>
                                <h3>{{ $member->name }}</h3>
                                <div class="ff2-meta">
                                    <span>{{ $member->role_label }}</span>
                                    <span>{{ $member->role_name ?? 'Responder' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="ff2-empty">No crew members are linked to your current team yet.</div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="ff2-card">
            <div class="ff2-card__body">
                <h3 class="ff2-card__title">Equipment Snapshot</h3>
                <p class="ff2-card__subtitle">Assigned equipment and station-ready assets.</p>
                <div class="ff2-list" style="margin-top:18px;">
                    @forelse($equipmentAssignments->take(8) as $assignment)
                        <div class="ff2-list-item">
                            <div>
                                <h3>{{ $assignment->equipment->equipment_name ?? 'Equipment record' }}</h3>
                                <div class="ff2-meta">
                                    <span>{{ $assignment->equipment->category->equipment_category_name ?? 'Uncategorized' }}</span>
                                    <span>Qty: {{ $assignment->quantity ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="ff2-empty">No equipment assignments are available yet.</div>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="ff2-card">
        <div class="ff2-card__body">
            <h3 class="ff2-card__title">Report Snapshot</h3>
            <p class="ff2-card__subtitle">Recent investigation records from the same report tables used by the current firefighter station page.</p>
            <div class="ff2-table-wrap" style="margin-top:16px;">
                <table class="ff2-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Location</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($spotReports->getCollection()->take(4) as $report)
                            <tr>
                                <td>Spot Report</td>
                                <td>#{{ $report->spot_investigation_report_id }}</td>
                                <td>{{ $report->incident->marker->title ?? 'No location title' }}</td>
                                <td>{{ optional($report->updated_at)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="ff2-empty">No spot reports yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
