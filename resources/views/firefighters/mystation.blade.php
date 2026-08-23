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
        $stationContactRaw = $station?->contact_number;
        $stationContact = $stationContactRaw ?: 'Contact not yet provided';
        $stationPhoneHref = $stationContactRaw ? preg_replace('/\D+/', '', $stationContactRaw) : null;
        $stationEmailRaw = $station?->email;
        $stationEmail = $stationEmailRaw ?: 'Email not yet provided';
        $stationAddressLabel = $stationAddress ?: 'Address not yet provided';
        $officerStatus = strtolower($stationOfficer?->status ?? 'available');
        $stationStatusLabel = $officerStatus === 'available'
            ? 'On duty'
            : ucwords(str_replace('-', ' ', $officerStatus));
    @endphp

    @include('partials.pwa-head')
</head>
<body class="firefighter-portal-shell">
    @include('firefighters.partials.shell-sidebar', ['active' => 'station'])

    <div data-ff-page-root="station">
    <main class="main-content">
        @include('firefighters.partials.shell-topbar', [
            'title' => 'My Station',
            'subtitle' => 'Station resources and reports'
        ])

        <div class="station-shell-page">
            <div class="station-shell">
                <section class="station-identity-card" aria-labelledby="stationIdentityTitle">
                    <div class="station-identity-main">
                        <div class="station-identity-avatar">
                            @if($stationImageUrl)
                                <img src="{{ $stationImageUrl }}" alt="{{ $stationName }}">
                            @else
                                <i class="fa-solid fa-building" aria-hidden="true"></i>
                            @endif
                        </div>

                        <div class="station-identity-copy">
                            <span class="station-chip"><i class="fa-solid fa-location-dot"></i> Responder Station</span>
                            <h1 class="station-title" id="stationIdentityTitle">{{ $stationName }}</h1>
                            <div class="station-identity-meta" aria-label="Station quick details">
                                {{-- <span><i class="fa-solid fa-hashtag"></i>{{ $stationCode }}</span> --}}
                                <span class="station-address-pill"><i class="fa-solid fa-location-dot"></i><span>{{ $stationAddressLabel }}</span></span>
                                <span class="status-pill station-duty-pill">{{ $stationStatusLabel }}</span>
                            </div>
                            <div class="station-contact-list">
                                @if($stationPhoneHref)
                                    <a href="tel:{{ $stationPhoneHref }}"><i class="fa-solid fa-phone"></i>{{ $stationContact }}</a>
                                @else
                                    <span><i class="fa-solid fa-phone-slash"></i>{{ $stationContact }}</span>
                                @endif
                                @if($stationEmailRaw)
                                    <a href="mailto:{{ $stationEmail }}"><i class="fa-solid fa-mail"></i>{{ $stationEmail }}</a>
                                @else
                                    <span><i class="fa-solid fa-envelope-open"></i>{{ $stationEmail }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="station-identity-actions">
                        <a href="#stationPersonnelSection" class="btn btn-outline"><i class="fa-solid fa-users"></i> Roster</a>
                        <a href="#stationReportsPanelContainer" class="btn btn-primary"><i class="fa-solid fa-file-lines"></i> Reports</a>
                    </div>
                </section>

                <section class="station-summary-grid" aria-label="Station operations summary">
                    <article class="station-summary-card" data-station-summary-card="officers">
                        <span class="station-summary-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <div>
                            <span class="station-summary-label">Officers on Duty</span>
                            <strong data-station-summary-value="officers">--</strong>
                            <small data-station-summary-context="officers">Loading roster</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="equipment">
                        <span class="station-summary-icon"><i class="fa-solid fa-toolbox"></i></span>
                        <div>
                            <span class="station-summary-label">Ready Equipment</span>
                            <strong data-station-summary-value="equipment">--</strong>
                            <small data-station-summary-context="equipment">Loading equipment</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="reports">
                        <span class="station-summary-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                        <div>
                            <span class="station-summary-label">Investigation Reports</span>
                            <strong data-station-summary-value="reports">--</strong>
                            <small data-station-summary-context="reports">Loading current tab</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="photos">
                        <span class="station-summary-icon"><i class="fa-solid fa-image"></i></span>
                        <div>
                            <span class="station-summary-label">Station Photos</span>
                            <strong data-station-summary-value="photos">--</strong>
                            <small data-station-summary-context="photos">Loading gallery</small>
                        </div>
                    </article>
                </section>

                <div id="stationPersonnelSection" class="station-section-shell" data-station-section="personnel">
                    <div class="section-shell-card station-loading-card">
                        <i class="fa-solid fa-users"></i>
                        <span>Loading personnel...</span>
                    </div>
                </div>

                <section class="section-card">
                    <div class="section-header">
                        <div>
                            <div class="section-title"><i class="fa-solid fa-file-lines"></i> Investigation Reports</div>
                        </div>
                        <div class="action-row">
                            <button type="button" class="btn btn-outline" data-open-report-modal="spot"><i class="fa-solid fa-plus"></i> New Spot</button>
                            <button type="button" class="btn btn-outline" data-open-report-modal="progress"><i class="fa-solid fa-plus"></i> New Progress</button>
                            <button type="button" class="btn btn-primary" data-open-report-modal="final"><i class="fa-solid fa-plus"></i> New Final</button>
                        </div>
                    </div>

                    <div class="report-tabs" id="reportTabs">
                        <button type="button" class="report-tab {{ $activeReportTab === 'spot' ? 'active' : '' }}" data-report-tab="spot">Spot Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'progress' ? 'active' : '' }}" data-report-tab="progress">Progress Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'final' ? 'active' : '' }}" data-report-tab="final">Final Investigation</button>
                    </div>

                    <div id="stationReportsPanelContainer" class="reports-shell-panel">
                        <div class="section-shell-card station-loading-card">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Loading reports...</span>
                        </div>
                    </div>
                </section>

                <div id="stationEquipmentSection" class="station-section-shell" data-station-section="equipment">
                    <div class="section-shell-card station-loading-card">
                        <i class="fa-solid fa-truck-medical"></i>
                        <span>Loading equipment...</span>
                    </div>
                </div>

                <div id="stationOverviewSection" class="station-section-shell" data-station-section="overview">
                    <div class="section-shell-card station-loading-card">
                        <i class="fa-solid fa-images"></i>
                        <span>Loading photos...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="report-modal" id="spotReportModal" role="dialog" aria-modal="true" aria-labelledby="spotReportModalTitle" data-report-state="idle">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title" id="spotReportModalTitle">
                    <span class="report-title-full">Create Spot Investigation Report</span>
                    <span class="report-title-mobile">New Spot Report</span>
                </div>
                <button type="button" class="report-close" data-close-report-modal="spot" aria-label="Close spot report form"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="spotReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid" data-report-form-content>
                        <div class="report-form-help full">
                            <button type="button" class="report-form-help__trigger" aria-describedby="spotReportPurposeHelp">
                                <i class="fa-solid fa-circle-info"></i><span>First findings guide</span>
                            </button>
                            <div class="report-form-help__tooltip" id="spotReportPurposeHelp" role="tooltip">
                                <strong>Use this for first findings.</strong>
                                <span>Record the scene, casualties, owner, damage estimate, and initial disposition.</span>
                                <small>Example: fire started in the kitchen, one injured, estimated damage 50,000.</small>
                            </div>
                        </div>
                        <div class="form-section-label">Incident reference</div>
                        <div class="form-group full">
                            <label class="form-label">Incident</label>
                            <select name="incident_id" class="form-select" data-station-ref="spot-incidents" required>
                                <option value="">Select incident</option>
                            </select>
                        </div>
                        <div class="form-section-label">Casualties and owner</div>
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
                        <div class="form-section-label">Report timeline</div>
                        <div class="form-group">
                            <label class="form-label">Report Completed</label>
                            <div class="form-auto-value"><i class="fa-solid fa-clock"></i><span>Recorded automatically when this report is submitted.</span></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Memorandum For</label>
                            <input type="text" name="memorandum_for" class="form-input" placeholder="Head of office receiving the report">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Occurred At</label>
                            <input type="datetime-local" name="fire_started" class="form-input">
                            <small class="table-muted">Confirm or correct the actual start time. Saving this verifies the occurrence time used by forecasting.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fire Out</label>
                            <input type="datetime-local" name="fire_out" class="form-input" id="spotFireOutInput">
                        </div>
                        <div class="form-section-label">Location and damage</div>
                        <div class="form-group full">
                            <label class="form-label">Place of Occurrence</label>
                            <input type="text" name="place_of_occurrence" class="form-input" placeholder="Street, barangay, city, landmark">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Damage</label>
                            <input type="number" min="0" step="0.01" name="estimated_damage" class="form-input" placeholder="Example: 50000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estimated Area</label>
                            <input type="number" min="0" step="0.01" name="estimated_area" class="form-input" placeholder="Example: 30" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Affected Establishments</label>
                            <input type="number" min="0" step="1" name="affected_establishments_count" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Highest Alarm</label>
                            <select name="highest_alarm_level" class="form-select">
                                <option value="">Select alarm level</option>
                                <option value="1st Alarm">1st Alarm</option>
                                <option value="2nd Alarm">2nd Alarm</option>
                                <option value="3rd Alarm">3rd Alarm</option>
                                <option value="4th Alarm">4th Alarm</option>
                                <option value="5th Alarm">5th Alarm</option>
                            </select>
                        </div>
                        <div class="form-section-label">Occupancy details</div>
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
                        <div class="form-section-label">Narrative and disposition</div>
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
                            <div class="narrative-action-row"><button type="button" class="btn btn-outline" data-generate-spot-narrative>
                                <i class="fa-solid fa-magic"></i> Generate Narrative
                            </button></div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Disposition</label>
                            <textarea name="disposition" class="form-textarea" rows="4" placeholder="Assessment, case status, and whether this will be elevated to a higher office"></textarea>
                        </div>
                        <div class="form-section-label">Photo documentation</div>
                        <div class="form-group full">
                            <label class="form-label">Proof Images</label>
                            <input type="file" name="proof_images[]" class="form-input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                            <small class="table-muted">Optional JPG or PNG images. Up to 20 images and 25 MB combined. Images are appended on a new page after the report content.</small>
                        </div>
                        <div class="form-section-label">Review and approval</div>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span class="submit-label-full">Submit Spot Report</span>
                        <span class="submit-label-mobile">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="progressReportModal" role="dialog" aria-modal="true" aria-labelledby="progressReportModalTitle" data-report-state="idle">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title" id="progressReportModalTitle">
                    <span class="report-title-full">Create Progress Investigation Report</span>
                    <span class="report-title-mobile">New Progress Report</span>
                </div>
                <button type="button" class="report-close" data-close-report-modal="progress" aria-label="Close progress report form"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="progressReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid" data-report-form-content>
                        <div class="report-form-hint full">
                            <strong>Use this for follow-up findings.</strong>
                            <span>Update the likely origin, cause, mission order, and new facts after investigation work continues.</span>
                            <small>Example notes: electrical origin, witness interview completed, mission order received.</small>
                        </div>
                        <div class="form-section-label">Source report</div>
                        <div class="form-group full">
                            <label class="form-label">Source Spot Report</label>
                            <div class="form-helper">Select the spot report this progress report continues.</div>
                            <select name="spot_investigation_id" class="form-select" data-station-ref="spot-reports" required>
                                <option value="">Select spot report</option>
                            </select>
                        </div>
                        <div class="form-section-label">Cause and orders</div>
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
                            <input type="text" name="responsible_arson" class="form-input" placeholder="Name or N/A">
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
                            <input type="text" name="mission_order_number" class="form-input" placeholder="Example: MO-2026-001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mission Order Date</label>
                            <input type="date" name="mission_order_date" class="form-input">
                        </div>
                        <div class="form-section-label">Narrative and disposition</div>
                        <div class="form-group full">
                            <label class="form-label">Fire Cause</label>
                            <textarea name="fire_cause" class="form-textarea" rows="3" placeholder="Example: unattended cooking, electrical overload, or still under investigation" required></textarea>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Facts of the Case</label>
                            <textarea name="facts_of_case" class="form-textarea" rows="6"></textarea>
                            <div class="narrative-action-row"><button type="button" class="btn btn-outline" data-generate-progress-narrative>
                                <i class="fa-solid fa-magic"></i> Generate Narrative
                            </button></div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Disposition</label>
                            <textarea name="disposition" class="form-textarea" rows="4"></textarea>
                        </div>
                        <div class="form-section-label">Photo documentation</div>
                        <div class="form-group full">
                            <label class="form-label">Proof Images</label>
                            <input type="file" name="proof_images[]" class="form-input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                            <small class="table-muted">Optional JPG or PNG images. Up to 20 images and 25 MB combined. Images are appended on a new page after the report content.</small>
                        </div>
                        <div class="form-section-label">Review and approval</div>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span class="submit-label-full">Submit Progress Report</span>
                        <span class="submit-label-mobile">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="finalReportModal" role="dialog" aria-modal="true" aria-labelledby="finalReportModalTitle" data-report-state="idle">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title" id="finalReportModalTitle">
                    <span class="report-title-full">Create Final Investigation Report</span>
                    <span class="report-title-mobile">New Final Report</span>
                </div>
                <button type="button" class="report-close" data-close-report-modal="final" aria-label="Close final report form"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="finalReportForm">
                <div class="report-modal-body">
                    <input type="hidden" name="station_officer_id" value="{{ $stationOfficer->station_officer_id }}">
                    <div class="form-grid" data-report-form-content>
                        <div class="report-form-hint full">
                            <strong>Use this for the closing report.</strong>
                            <span>Summarize verified damage, final cause, supporting documents, findings, and recommendation.</span>
                            <small>Example outcome: accidental electrical fire, final damage 75000, case closed.</small>
                        </div>
                        <div class="form-section-label">Source report</div>
                        <div class="form-group full">
                            <label class="form-label">Source Progress Report</label>
                            <div class="form-helper">Select the progress report this final report completes.</div>
                            <select name="progress_investigation_id" class="form-select" data-station-ref="progress-reports" required>
                                <option value="">Select progress report</option>
                            </select>
                        </div>
                        <div class="form-section-label">Damage and timeline</div>
                        <div class="form-group">
                            <label class="form-label">Total Damage</label>
                            <input type="number" min="0" step="0.01" name="total_damage" class="form-input" placeholder="Example: 75000" required>
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
                        <div class="form-section-label">Location and affected parties</div>
                        <div class="form-group full">
                            <label class="form-label">Establishment Burned</label>
                            <input type="text" name="establishment_burned" class="form-input" placeholder="House, store, warehouse, or N/A">
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
                        <div class="form-section-label">Documents and findings</div>
                        <div class="form-group full">
                            <label class="form-label">Substantiating Documents</label>
                            <div class="checkbox-grid" id="finalSubstantiatingDocuments"></div>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Facts of the Case</label>
                            <textarea name="facts_of_case" class="form-textarea" rows="5"></textarea>
                            <div class="narrative-action-row"><button type="button" class="btn btn-outline" data-generate-final-narrative>
                                <i class="fa-solid fa-magic"></i> Generate Narrative
                            </button></div>
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
                        <div class="form-section-label">Photo documentation</div>
                        <div class="form-group full">
                            <label class="form-label">Proof Images</label>
                            <input type="file" name="proof_images[]" class="form-input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                            <small class="table-muted">Optional JPG or PNG images. Up to 20 images and 25 MB combined. Images are appended on a new page after the report content.</small>
                        </div>
                        <div class="form-section-label">Review and approval</div>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span class="submit-label-full">Submit Final Report</span>
                        <span class="submit-label-mobile">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="report-modal" id="manageReportModal" role="dialog" aria-modal="true" aria-labelledby="manageReportModalTitle">
        <div class="report-modal-panel report-modal-panel--manage">
            <div class="report-modal-header">
                <div class="report-modal-title" id="manageReportModalTitle">Manage Investigation Report</div>
                <button type="button" class="report-close" data-close-manage-modal aria-label="Close report details"><i class="fa-solid fa-times"></i></button>
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
                proofImageDeleteTemplate: @json($proofImageDeleteTemplate),
                proofImageOrderTemplate: @json($proofImageOrderTemplate),
                sirTemplate: @json(route('firefighter.spot-reports.sir.word', ['spotReportId' => '__SPOT_REPORT_ID__'])),
                pirTemplate: @json(route('firefighter.progress-reports.pir.word', ['progressReportId' => '__PROGRESS_REPORT_ID__'])),
                firTemplate: @json(route('firefighter.final-reports.fir.word', ['finalReportId' => '__FINAL_REPORT_ID__'])),
            },
            finalSubstantiatingDocumentOptions: @json($finalSubstantiatingDocumentOptions),
        };
    </script>

    @vite('resources/js/firefighter/station-shell.js')
    </div>
    @include('partials.pwa-body')
</body>
</html>
