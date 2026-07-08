<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - My Station</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite([
        'resources/css/app.css',
        'resources/css/firefighters/portal.css',
        'resources/css/firefighters/station-shell.css',
    ])

    @php
        $stationName = $station?->station_name ?? 'Station not assigned';
        $stationCode = $station?->station_code ?? 'N/A';
        $stationContact = $station?->contact_number ?? 'No contact number on file';
        $stationEmail = $station?->email ?? 'No station email on file';
    @endphp

    @include('partials.pwa-head')
</head>
<body>
    @include('firefighters.partials.shell-sidebar', ['active' => 'station'])

    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'My Station',
            'subtitle' => 'Station profile, officers, crew assignments, equipment inventory, and investigation reports'
        ])

        <div data-ff-page-root="station" class="station-shell-page">
            <div class="station-shell">
                <section class="station-hero">
                    <div class="station-hero-media">
                        @if($stationImageUrl)
                            <img src="{{ $stationImageUrl }}" alt="{{ $stationName }}">
                        @else
                            <i class="fa-solid fa-building-shield"></i>
                        @endif
                    </div>

                    <div class="station-hero-content">
                        <span class="station-chip"><i class="fa-solid fa-fire"></i> BFP Station Details</span>
                        <div>
                            <h1 class="station-title">{{ $stationName }}</h1>
                            <p class="station-subtitle">
                                This page is scoped to your assigned station only. It renders the station shell first, then streams roster, equipment, and investigation data into each section.
                            </p>
                        </div>

                        <div class="station-hero-grid">
                            <div class="hero-detail">
                                <div class="hero-detail-label">Station Code</div>
                                <div class="hero-detail-value">{{ $stationCode }}</div>
                            </div>
                            <div class="hero-detail">
                                <div class="hero-detail-label">Contact Number</div>
                                <div class="hero-detail-value">{{ $stationContact }}</div>
                            </div>
                            <div class="hero-detail">
                                <div class="hero-detail-label">Email Address</div>
                                <div class="hero-detail-value">{{ $stationEmail }}</div>
                            </div>
                            <div class="hero-detail">
                                <div class="hero-detail-label">Station Address</div>
                                <div class="hero-detail-value">{{ $stationAddress ?: 'No station address on file' }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <div id="stationOverviewSection" class="station-section-shell" data-station-section="overview">
                    <div class="section-shell-card">
                        <div class="shell-heading">
                            <div class="shell-line shell-line-lg"></div>
                            <div class="shell-line shell-line-md"></div>
                        </div>
                        <div class="shell-grid shell-grid-two">
                            <div class="shell-panel shell-panel-tall"></div>
                            <div class="shell-panel shell-panel-tall"></div>
                        </div>
                    </div>
                </div>

                <div id="stationPersonnelSection" class="station-section-shell" data-station-section="personnel">
                    <div class="section-shell-card">
                        <div class="shell-heading">
                            <div class="shell-line shell-line-lg"></div>
                            <div class="shell-line shell-line-md"></div>
                        </div>
                        <div class="shell-grid shell-grid-two">
                            <div class="shell-panel shell-panel-medium"></div>
                            <div class="shell-panel shell-panel-large"></div>
                        </div>
                    </div>
                </div>

                <section class="section-card">
                    <div class="section-header">
                        <div>
                            <div class="section-title"><i class="fa-solid fa-file-lines"></i> Report Management</div>
                            <div class="section-subtitle">Only spot, progress, and final investigation reports scoped to this station are shown here.</div>
                        </div>
                        <div class="action-row">
                            <button type="button" class="btn btn-outline" data-open-report-modal="spot"><i class="fa-solid fa-file-circle-plus"></i> New Spot</button>
                            <button type="button" class="btn btn-outline" data-open-report-modal="progress"><i class="fa-solid fa-file-circle-plus"></i> New Progress</button>
                            <button type="button" class="btn btn-primary" data-open-report-modal="final"><i class="fa-solid fa-file-circle-plus"></i> New Final</button>
                        </div>
                    </div>

                    <div class="report-tabs" id="reportTabs">
                        <button type="button" class="report-tab {{ $activeReportTab === 'spot' ? 'active' : '' }}" data-report-tab="spot">Spot Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'progress' ? 'active' : '' }}" data-report-tab="progress">Progress Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'final' ? 'active' : '' }}" data-report-tab="final">Final Investigation</button>
                    </div>

                    <div id="stationReportsPanelContainer" class="reports-shell-panel">
                        <div class="section-shell-card">
                            <div class="shell-grid">
                                <div class="shell-panel shell-panel-large"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <div id="stationEquipmentSection" class="station-section-shell" data-station-section="equipment">
                    <div class="section-shell-card">
                        <div class="shell-heading">
                            <div class="shell-line shell-line-lg"></div>
                            <div class="shell-line shell-line-md"></div>
                        </div>
                        <div class="shell-grid">
                            <div class="shell-panel shell-panel-large"></div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                window.initFirefighterMyStationPage?.();
            </script>
        </div>
    </main>

    <div class="report-modal" id="spotReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Spot Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="spot"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="spotReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Incident</label>
                            <select name="incident_id" class="form-select" data-station-ref="spot-incidents" required>
                                <option value="">Select incident</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Dead</label>
                            <input type="number" min="0" step="1" name="dead" class="form-input" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Injured</label>
                            <input type="number" min="0" step="1" name="injured" class="form-input" value="0">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Owner</label>
                            <input type="text" name="owner" class="form-input" placeholder="Owner or responsible party">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Report Completed</label>
                            <input type="datetime-local" name="report_completed_at" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Memorandum For</label>
                            <input type="text" name="memorandum_for" class="form-input" placeholder="Head of office receiving the report">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Started</label>
                            <input type="datetime-local" name="fire_started" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Out</label>
                            <input type="datetime-local" name="fire_out" class="form-input" id="spotFireOutInput">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Place of Occurrence</label>
                            <input type="text" name="place_of_occurrence" class="form-input" placeholder="Street, barangay, city, landmark">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Damage</label>
                            <input type="number" min="0" step="0.01" name="estimated_damage" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Area</label>
                            <input type="number" min="0" step="0.01" name="estimated_area" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Affected Establishments</label>
                            <input type="number" min="0" step="1" name="affected_establishments_count" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Highest Alarm</label>
                            <input type="text" name="highest_alarm_level" class="form-input" placeholder="Example: 1st alarm, 2nd alarm">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Occupancy</label>
                            <select name="occupancy" class="form-select" required>
                                <option value="">Select occupancy</option>
                                <option value="residential">Residential</option>
                                <option value="commercial">Commercial</option>
                                <option value="industrial">Industrial</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Involved Structure</label>
                            <input type="text" name="involved_structure" class="form-input" placeholder="House, warehouse, stall, office">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Establishment Name</label>
                            <input type="text" name="establishment_name" class="form-input" placeholder="Establishment or business name">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Occupant</label>
                            <input type="text" name="occupant" class="form-input" placeholder="Occupant or tenant name">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Raw Description / Field Notes</label>
                            <textarea name="description" class="form-textarea" rows="5" placeholder="Type quick notes in Tagalog or English about what happened, observed fire conditions, witness statements, actions taken, and other scene details"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Fatality Details</label>
                            <textarea name="fatality_details" class="form-textarea" placeholder="Initial identity, location found, and other casualty details"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Details of Investigation / Narrative</label>
                            <textarea name="details_of_investigation" class="form-textarea" rows="6" placeholder="Full fire incident narration, area affected, weather condition, observed conditions, and other initial findings"></textarea>
                            <div class="table-muted">Use field notes first, then let the system draft a cleaner narrative that you can still edit.</div>
                            <button type="button" class="btn btn-outline" data-generate-spot-narrative style="margin-top: 0.85rem; width: fit-content;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Narrative
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Disposition</label>
                            <textarea name="disposition" class="form-textarea" rows="4" placeholder="Assessment, case status, and whether this will be elevated to a higher office"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Noted By</label>
                            <select name="noted_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Approved By</label>
                            <select name="approved_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="spot">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Spot Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="progressReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Progress Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="progress"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="progressReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Spot Investigation Report</label>
                            <select name="spot_investigation_id" class="form-select" data-station-ref="spot-reports" required>
                                <option value="">Select spot report</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Origin</label>
                            <select name="fire_origin_id" class="form-select" data-station-ref="fire-origins">
                                <option value="">Select fire origin</option>
                            </select>
                        </div>
                        <div class="form-group" data-fire-origin-other-group style="display:none;">
                            <label class="form-label">Specify Fire Origin</label>
                            <input type="text" name="fire_origin_other" class="form-input" placeholder="Type the fire origin">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Responsible Arson</label>
                            <input type="text" name="responsible_arson" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Report Completed</label>
                            <input type="datetime-local" name="report_completed_at" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Memorandum For</label>
                            <input type="text" name="memorandum_for" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mission Order Number</label>
                            <input type="text" name="mission_order_number" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mission Order Date</label>
                            <input type="date" name="mission_order_date" class="form-input">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Fire Cause</label>
                            <textarea name="fire_cause" class="form-textarea" rows="3" required></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Facts of the Case</label>
                            <textarea name="facts_of_case" class="form-textarea" rows="6"></textarea>
                            <div class="table-muted">Generate a refined progress narrative from the linked spot report context and your current findings.</div>
                            <button type="button" class="btn btn-outline" data-generate-progress-narrative style="margin-top: 0.85rem; width: fit-content;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Narrative
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Disposition</label>
                            <textarea name="disposition" class="form-textarea" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Noted By</label>
                            <select name="noted_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Approved By</label>
                            <select name="approved_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="progress">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Progress Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="finalReportModal">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title">Create Final Investigation Report</div>
                <button type="button" class="report-close" data-close-report-modal="final"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="finalReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Progress Investigation Report</label>
                            <select name="progress_investigation_id" class="form-select" data-station-ref="progress-reports" required>
                                <option value="">Select progress report</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Total Damage</label>
                            <input type="number" min="0" step="0.01" name="total_damage" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Report Completed</label>
                            <input type="datetime-local" name="report_completed_at" class="form-input">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Office Address</label>
                            <textarea name="office_address" class="form-textarea" rows="2"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Exact Address</label>
                            <textarea name="exact_address" class="form-textarea" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Memorandum For</label>
                            <input type="text" name="memorandum_for" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alarm Time</label>
                            <input type="datetime-local" name="alarm_at" class="form-input">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Establishment Burned</label>
                            <input type="text" name="establishment_burned" class="form-input">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Fire Victim/s</label>
                            <textarea name="fire_victims" class="form-textarea" rows="2"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Origin of Fire</label>
                            <textarea name="origin_of_fire" class="form-textarea" rows="2"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Cause of Fire</label>
                            <textarea name="cause_of_fire" class="form-textarea" rows="2"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Substantiating Documents</label>
                            <div class="checkbox-grid" id="finalSubstantiatingDocuments"></div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Facts of the Case</label>
                            <textarea name="facts_of_case" class="form-textarea" rows="5"></textarea>
                            <div class="table-muted">Generate the final investigation narrative sections from the linked progress and spot reports, then fine-tune them before saving.</div>
                            <button type="button" class="btn btn-outline" data-generate-final-narrative style="margin-top: 0.85rem; width: fit-content;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Narrative
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Discussion</label>
                            <textarea name="discussion" class="form-textarea" rows="5"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Findings</label>
                            <textarea name="findings" class="form-textarea" rows="4"></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Recommendation</label>
                            <textarea name="recommendation" class="form-textarea" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Noted By</label>
                            <select name="noted_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Approved By</label>
                            <select name="approved_by_station_officer_id" class="form-select" data-station-ref="officers">
                                <option value="">Select officer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="report-modal-footer">
                    <button type="button" class="btn btn-outline" data-close-report-modal="final">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Final Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="manageReportModal">
        <div class="report-modal-panel report-modal-panel--manage">
            <div class="report-modal-header">
                <div class="report-modal-title" id="manageReportModalTitle">Manage Investigation Report</div>
                <button type="button" class="report-close" data-close-manage-modal><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="report-modal-body" id="manageReportModalBody"></div>
            <div class="report-modal-footer" id="manageReportModalFooter"></div>
        </div>
    </div>

    <div id="reportToastStack" class="report-toast-stack"></div>

    <script>
        window.firefighterMyStationConfig = {
            csrfToken: @json(csrf_token()),
            activeReportTab: @json($activeReportTab),
            currentOfficerName: @json($currentOfficerName),
            stationId: @json($stationId),
            routes: {
                overview: @json(route('firefighter.my-station.overview')),
                personnel: @json(route('firefighter.my-station.personnel')),
                equipment: @json(route('firefighter.my-station.equipment')),
                reports: @json(route('firefighter.my-station.reports')),
                modalData: @json(route('firefighter.my-station.modal-data')),
                spotNarrative: '/api/spot-investigation/draft-narrative',
                progressNarrative: '/api/progress-investigation/draft-narrative',
                finalNarrative: '/api/final-investigation/draft-narrative',
                spotCreate: '/api/spot-investigation',
                progressCreate: '/api/progress-investigation',
                finalCreate: '/api/final-investigation',
                reportManageBase: '/api',
                sirTemplate: @json(route('firefighter.spot-reports.sir.word', ['spotReportId' => '__SPOT_REPORT_ID__'])),
                pirTemplate: @json(route('firefighter.progress-reports.pir.word', ['progressReportId' => '__PROGRESS_REPORT_ID__'])),
                firTemplate: @json(route('firefighter.final-reports.fir.word', ['finalReportId' => '__FINAL_REPORT_ID__'])),
            },
            finalSubstantiatingDocumentOptions: @json($finalSubstantiatingDocumentOptions),
        };
    </script>

    @vite('resources/js/firefighter/station-shell.js')
    @include('partials.pwa-body')
</body>
</html>
