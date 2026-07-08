@if($tab === 'progress')
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
                                <a class="table-action edit" href="{{ route('firefighter.progress-reports.pir.word', ['progressReportId' => $report->progress_investigation_report_id]) }}">Word</a>
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
@elseif($tab === 'final')
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
                @forelse($reports as $report)
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
                                <a class="table-action edit" href="{{ route('firefighter.final-reports.fir.word', ['finalReportId' => $report->final_investigation_report_id]) }}">Word</a>
                                <button type="button" class="table-action edit" data-report-action="edit" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">Edit</button>
                                <button type="button" class="table-action delete" data-report-action="delete" data-report-type="final" data-report-id="{{ $report->final_investigation_report_id }}">Delete</button>
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
                                <i class="fa-solid fa-file-circle-xmark"></i>
                                <div>No spot investigation reports have been filed for this station yet.</div>
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
