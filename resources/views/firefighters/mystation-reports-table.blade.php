@if($tab === 'aar')
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Activity</th><th>Date</th><th>Type</th><th>Prepared By</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody id="aarReportsTableBody">
                @forelse($reports as $report)
                    <tr id="aar-report-{{ $report->after_activity_report_id }}">
                        <td data-label="Activity"><div class="table-title">{{ $report->activity_title }}</div><div class="table-muted">{{ $report->activity_location ?: 'Location not recorded' }}</div></td>
                        <td data-label="Date">{{ optional($report->activity_date)->format('M d, Y') }}</td>
                        <td data-label="Type">{{ ucfirst($report->activity_type ?: 'Activity') }}</td>
                        <td data-label="Prepared by">@include('firefighters.partials.person-trigger', ['personOfficer' => $report->investigator, 'contextLabel' => 'AAR prepared by'])</td>
                        <td data-label="Status"><span class="table-muted">{{ str($report->review_status ?: 'submitted')->replace('_', ' ')->title() }}</span></td>
                        <td data-label="Actions"><div class="table-actions">
                            <button type="button" class="table-action view" data-report-action="view" data-report-type="aar" data-report-id="{{ $report->after_activity_report_id }}">View</button>
                            <a class="table-action edit" href="{{ route('firefighter.after-activity-reports.word', $report) }}">Word</a>
                            <a class="table-action edit" href="{{ route('firefighter.after-activity-reports.pdf', $report) }}">PDF</a>
                            @if($report->review_status !== 'approved')<button type="button" class="table-action edit" data-report-action="edit" data-report-type="aar" data-report-id="{{ $report->after_activity_report_id }}">Edit</button>
                            <button type="button" class="table-action delete" data-report-action="delete" data-report-type="aar" data-report-id="{{ $report->after_activity_report_id }}">Delete</button>@endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-box"><i class="fa-solid fa-box-open"></i><div>No After Activity Reports yet.</div></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($tab === 'progress')
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
                @forelse($reports as $report)
                    @php
                        $hasFinal = (bool) $report->finalInvestigation;
                        $hasSecond = (bool) $report->secondProgressInvestigation;
                    @endphp
                    <tr id="progress-report-{{ $report->progress_investigation_report_id }}">
                        <td data-label="Incident">
                            <div class="table-title">{{ $report->spotInvestigation?->incident?->marker?->title ?? 'Fire incident' }}</div>
                            <div class="table-muted">{{ $report->report_stage_label }}</div>
                        </td>
                        <td data-label="Fire origin">{{ $report->fireOrigin?->origin ?? 'N/A' }}</td>
                        <td data-label="Cause">{{ $report->fire_cause ?? 'N/A' }}</td>
                        <td data-label="Responsible party">{{ $report->responsible_arson ?? 'Not specified' }}</td>
                        <td data-label="Investigator">@include('firefighters.partials.person-trigger', ['personOfficer' => $report->investigator, 'contextLabel' => 'Progress report investigator'])</td>
                        <td data-label="Actions">
                            <div class="table-actions">
                                <button type="button" class="table-action view" data-report-action="view" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">View</button>
                                <a class="table-action edit" href="{{ route('firefighter.progress-reports.pir.word', ['progressReportId' => $report->progress_investigation_report_id]) }}">Word</a>
                                @unless($hasFinal)
                                    <button type="button" class="table-action edit" data-report-action="edit" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">Edit</button>
                                @endunless
                                @unless($hasFinal || $hasSecond)
                                    <button type="button" class="table-action delete" data-report-action="delete" data-report-type="progress" data-report-id="{{ $report->progress_investigation_report_id }}">Delete</button>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-box">
                                <i class="fa-solid fa-box-open"></i>
                                <div>No progress reports yet.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@elseif($tab === 'final')
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Progress Report</th>
                    <th>Exact Address</th>
                    <th>Total Damage</th>
                    <th>Investigator</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="finalReportsTableBody">
                @forelse($reports as $report)
                    <tr id="final-report-{{ $report->final_investigation_report_id }}">
                        <td data-label="Incident">
                            <div class="table-title">{{ $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title ?? 'Fire incident' }}</div>
                            <div class="table-muted">Progress report</div>
                        </td>
                        <td data-label="Address">{{ $report->exact_address ?? 'N/A' }}</td>
                        <td data-label="Total damage">PHP {{ number_format((float) ($report->total_damage ?? 0), 2) }}</td>
                        <td data-label="Investigator">@include('firefighters.partials.person-trigger', ['personOfficer' => $report->investigator, 'contextLabel' => 'Final report investigator'])</td>
                        <td data-label="Created">{{ optional($report->created_at)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        <td data-label="Actions">
                            <div class="table-actions">
                                <button type="button" class="table-action view" data-report-action="view" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">View</button>
                                <a class="table-action edit" href="{{ route('firefighter.final-reports.fir.word', ['finalReportId' => $report->final_investigation_report_id]) }}">Word</a>
                                <button type="button" class="table-action edit" data-report-action="edit" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">Edit</button>
                                <button type="button" class="table-action delete" data-report-action="delete" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-box">
                                <i class="fa-solid fa-box-open"></i>
                                <div>No final reports yet.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
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
                @forelse($reports as $report)
                    @php $hasProgress = (bool) $report->progressInvestigation; @endphp
                    <tr id="spot-report-{{ $report->spot_investigation_report_id }}">
                        <td data-label="Incident">
                            <div class="table-title">{{ $report->incident?->marker?->title ?? 'Fire incident' }}</div>
                            <div class="table-muted">Spot report</div>
                        </td>
                        <td data-label="Investigator">@include('firefighters.partials.person-trigger', ['personOfficer' => $report->investigator, 'contextLabel' => 'Spot report investigator'])</td>
                        <td data-label="Fire timeline">{{ optional($report->fire_started)->format('M d, Y h:i A') ?: 'N/A' }}<br>{{ optional($report->fire_out)->format('M d, Y h:i A') ?: 'N/A' }}</td>
                        <td data-label="Damage">PHP {{ number_format((float) ($report->estimated_damage ?? 0), 2) }}</td>
                        <td data-label="Occupancy">{{ ucfirst($report->occupancy ?? 'n/a') }}</td>
                        <td data-label="Actions">
                            <div class="table-actions">
                                <button type="button" class="table-action view" data-report-action="view" data-report-type="spot" data-report-id="{{ $report->spot_investigation_report_id }}">View</button>
                                <a class="table-action edit" href="{{ route('firefighter.spot-reports.sir.word', ['spotReportId' => $report->spot_investigation_report_id]) }}">Word</a>
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
                                <i class="fa-solid fa-box-open"></i>
                                <div>No spot reports yet.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif

@if($reports->hasPages())
    <div style="padding-top: 16px;">
        {{ $reports->appends(['tab' => $tab])->links() }}
    </div>
@endif
