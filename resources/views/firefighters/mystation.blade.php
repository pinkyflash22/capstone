<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - My Station</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite([
        'resources/css/app.css',
        'resources/css/firefighters/portal.css',
        'resources/css/firefighters/station-shell.css',
        'resources/css/firefighters/my-station.css',
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
        $currentOfficerInitials = collect(preg_split('/\s+/', trim($currentOfficerName)) ?: [])
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->join('') ?: 'FF';
        $currentOfficerPayload = [
            'name' => $currentOfficerName,
            'rank' => $stationOfficer?->rank ?: 'Rank not recorded',
            'role' => ucfirst($user?->role?->role ?? 'firefighter'),
            'status' => ucwords(str_replace('-', ' ', strtolower($stationOfficer?->status ?: 'available'))),
            'email' => $user?->email,
            'contact' => $user?->contact_number,
            'image' => $user?->profile_thumbnail_url ?: $user?->profile_image_url,
            'initials' => $currentOfficerInitials,
        ];
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
                            <span class="station-chip"><i class="fa-solid fa-shield-halved"></i> Station command workspace</span>
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
                        <a href="#stationPersonnelSection" class="btn btn-primary"><i class="fa-solid fa-people-group"></i> Response team</a>
                        <a href="#stationEquipmentSection" class="btn btn-outline"><i class="fa-solid fa-truck-medical"></i> Readiness</a>
                        <a href="#stationReportsSection" class="btn btn-outline"><i class="fa-solid fa-file-lines"></i> Reports</a>
                    </div>
                </section>

                <header class="station-workspace-heading">
                    <div>
                        <span class="station-workspace-kicker"><i class="fa-solid fa-signal"></i> Live station overview</span>
                        <h2>Operational readiness</h2>
                        <p>Response personnel and deployable resources are prioritized before administrative documentation.</p>
                    </div>
                    <span class="station-workspace-order"><i class="fa-solid fa-arrow-down-wide-short"></i> Priority order</span>
                </header>

                <section class="station-summary-grid" aria-label="Station operations summary">
                    <article class="station-summary-card" data-station-summary-card="officers">
                        <span class="station-summary-icon"><i class="fa-solid fa-user-shield"></i></span>
                        <div>
                            <span class="station-summary-label">Available officers</span>
                            <strong data-station-summary-value="officers">--</strong>
                            <small data-station-summary-context="officers">Loading roster</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="equipment">
                        <span class="station-summary-icon"><i class="fa-solid fa-toolbox"></i></span>
                        <div>
                            <span class="station-summary-label">Deployable equipment</span>
                            <strong data-station-summary-value="equipment">--</strong>
                            <small data-station-summary-context="equipment">Loading equipment</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="reports">
                        <span class="station-summary-icon"><i class="fa-solid fa-clipboard-list"></i></span>
                        <div>
                            <span class="station-summary-label">Documentation</span>
                            <strong data-station-summary-value="reports">--</strong>
                            <small data-station-summary-context="reports">Loading current tab</small>
                        </div>
                    </article>
                    <article class="station-summary-card" data-station-summary-card="photos">
                        <span class="station-summary-icon"><i class="fa-solid fa-image"></i></span>
                        <div>
                            <span class="station-summary-label">Station gallery</span>
                            <strong data-station-summary-value="photos">--</strong>
                            <small data-station-summary-context="photos">Loading gallery</small>
                        </div>
                    </article>
                </section>

                <div class="station-flow-heading">
                    <span class="station-flow-index">01</span>
                    <div><span>Highest priority</span><h2>Response personnel</h2><p>See who is available, assigned, or currently part of your response crew.</p></div>
                </div>
                <div id="stationPersonnelSection" class="station-section-shell" data-station-section="personnel">
                    <div class="section-shell-card station-loading-card">
                        <i class="fa-solid fa-users"></i>
                        <span>Loading personnel...</span>
                    </div>
                </div>

                <div class="station-flow-heading">
                    <span class="station-flow-index">02</span>
                    <div><span>Deployment readiness</span><h2>Vehicles and equipment</h2><p>Review resources that can be deployed before preparing station documentation.</p></div>
                </div>
                <div id="stationEquipmentSection" class="station-section-shell" data-station-section="equipment">
                    <div class="section-shell-card station-loading-card">
                        <i class="fa-solid fa-truck-medical"></i>
                        <span>Loading equipment...</span>
                    </div>
                </div>

                <div class="station-flow-heading">
                    <span class="station-flow-index">03</span>
                    <div><span>After-action records</span><h2>Station documentation</h2><p>Create investigation reports and AARs for scheduled or unscheduled BFP work.</p></div>
                </div>
                <section class="section-card station-reports-card" id="stationReportsSection">
                    <div class="section-header">
                        <div>
                            <div class="section-title"><i class="fa-solid fa-file-lines"></i> Investigation Reports</div>
                            <div class="section-subtitle">Station-scoped reports, ordered from the latest record.</div>
                        </div>
                        <div class="action-row">
                            <button type="button" class="btn btn-outline" data-open-report-modal="spot"><i class="fa-solid fa-plus"></i> New Spot</button>
                            <button type="button" class="btn btn-outline" data-open-report-modal="progress"><i class="fa-solid fa-plus"></i> New Progress</button>
                            <button type="button" class="btn btn-primary" data-open-report-modal="final"><i class="fa-solid fa-plus"></i> New Final</button>
                            <button type="button" class="btn btn-primary" data-open-report-modal="aar"><i class="fa-solid fa-plus"></i> New AAR</button>
                        </div>
                    </div>

                    <div class="report-tabs" id="reportTabs">
                        <button type="button" class="report-tab {{ $activeReportTab === 'spot' ? 'active' : '' }}" data-report-tab="spot">Spot Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'progress' ? 'active' : '' }}" data-report-tab="progress">Progress Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'final' ? 'active' : '' }}" data-report-tab="final">Final Investigation</button>
                        <button type="button" class="report-tab {{ $activeReportTab === 'aar' ? 'active' : '' }}" data-report-tab="aar">After Activity Report</button>
                    </div>

                    <div id="stationReportsPanelContainer" class="reports-shell-panel">
                        <div class="section-shell-card station-loading-card">
                            <i class="fa-solid fa-file-lines"></i>
                            <span>Loading reports...</span>
                        </div>
                    </div>
                </section>

                <div class="station-flow-heading station-flow-heading--supporting">
                    <span class="station-flow-index">04</span>
                    <div><span>Supporting information</span><h2>Station gallery</h2><p>Reference photos and visual records for the assigned station.</p></div>
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

    <div class="crew-member-modal" id="crewMemberDetailModal" aria-hidden="true">
        <button type="button" class="crew-member-modal__backdrop" data-crew-member-close aria-label="Close personnel details"></button>
        <section class="crew-member-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="crewMemberDetailName">
            <button type="button" class="crew-member-modal__close" data-crew-member-close aria-label="Close personnel details"><i class="fa-solid fa-xmark"></i></button>
            <div class="crew-member-modal__identity">
                <div class="crew-member-modal__photo" data-crew-modal-photo></div>
                <div><span class="crew-member-modal__eyebrow" data-crew-modal-group>Station personnel</span><h3 id="crewMemberDetailName" data-crew-modal-name>Firefighter</h3><p data-crew-modal-position>Position</p></div>
            </div>
            <dl class="crew-member-modal__details">
                <div><dt>Rank</dt><dd data-crew-modal-rank>Not recorded</dd></div>
                <div><dt>Role</dt><dd data-crew-modal-role>Firefighter</dd></div>
                <div><dt>Status</dt><dd data-crew-modal-status>Available</dd></div>
                <div><dt>Contact</dt><dd data-crew-modal-contact>Not provided</dd></div>
            </dl>
            <a class="crew-member-modal__email" data-crew-modal-email hidden><i class="fa-solid fa-envelope"></i><span></span></a>
        </section>
    </div>

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
                        <div class="form-group full spot-location-picker" data-spot-location-picker>
                            <label class="form-label">Fire Incident Pin</label>
                            <div class="spot-location-picker__map" data-spot-location-map></div>
                            <div class="spot-location-picker__fields">
                                <label>
                                    <span>Latitude</span>
                                    <input type="number" step="0.0000001" min="-90" max="90" name="incident_location_lat" class="form-input" data-spot-location-lat>
                                </label>
                                <label>
                                    <span>Longitude</span>
                                    <input type="number" step="0.0000001" min="-180" max="180" name="incident_location_lng" class="form-input" data-spot-location-lng>
                                </label>
                                <button type="button" class="btn btn-outline spot-location-picker__reported" data-spot-location-reported disabled>
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                    <span>Reported point</span>
                                </button>
                            </div>
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

    <div class="report-modal" id="aarReportModal" role="dialog" aria-modal="true" aria-labelledby="aarReportModalTitle" data-report-state="idle">
        <div class="report-modal-panel">
            <div class="report-modal-header">
                <div class="report-modal-title" id="aarReportModalTitle"><span class="report-title-full">Create After Activity Report</span><span class="report-title-mobile">New AAR</span></div>
                <button type="button" class="report-close" data-close-report-modal="aar" aria-label="Close After Activity Report form"><i class="fa-solid fa-times"></i></button>
            </div>
            <form id="aarReportForm">
                <div class="report-modal-body">
                    <div class="form-grid" data-report-form-content>
                        <div class="report-form-hint full"><strong>Record any completed BFP activity.</strong><span>Link a scheduled activity, or leave it blank for an unscheduled rescue, operation, or community service.</span></div>
                        <div class="form-section-label">Activity source</div>
                        <div class="form-group full">
                            <label class="form-label">Scheduled Activity (Optional)</label>
                            <div class="form-helper">For an unscheduled operation, keep the first option and enter the details below.</div>
                            <select name="event_activity_id" class="form-select" data-station-ref="aar-activities"><option value="">Unscheduled operation or activity</option></select>
                        </div>
                        <div class="form-group full"><label class="form-label">Activity Title</label><input type="text" name="activity_title" class="form-input" placeholder="Example: Rescue operation or fire safety seminar" required></div>
                        <div class="form-group"><label class="form-label">Activity Type</label><input type="text" name="activity_type" class="form-input" placeholder="Rescue, seminar, drill, inspection"></div>
                        <div class="form-group"><label class="form-label">Activity Date</label><input type="date" name="activity_date" class="form-input" max="{{ now()->format('Y-m-d') }}" required></div>
                        <div class="form-group full"><label class="form-label">Location</label><input type="text" name="activity_location" class="form-input" placeholder="Barangay, facility, or exact location"></div>
                        <div class="form-group full"><label class="form-label">Participants</label><textarea name="participants_text" class="form-textarea" rows="4" placeholder="One participant or group per line, e.g. FO1 Juan Dela Cruz — Team Leader"></textarea><small class="table-muted">Enter one participant, unit, or partner organization per line.</small></div>
                        <div class="form-group full"><label class="form-label">Deployed Resources</label><textarea name="deployed_resources_text" class="form-textarea" rows="4" placeholder="One resource per line, e.g. Engine 1 — 1 unit; Hose — 200 meters"></textarea><small class="table-muted">Include apparatus, equipment, supplies, and quantities.</small></div>
                        <div class="form-section-label">Memorandum</div>
                        <div class="form-group"><label class="form-label">Memorandum For</label><input type="text" name="memorandum_for" class="form-input" placeholder="Respective head of office"></div>
                        <div class="form-group"><label class="form-label">Report Completed</label><input type="datetime-local" name="report_completed_at" class="form-input"></div>
                        <div class="form-group full"><label class="form-label">1. References</label><textarea name="references" class="form-textarea" rows="3" placeholder="Enter references, or write None"></textarea></div>
                        <div class="form-group full"><label class="form-label">2. Background</label><textarea name="background" class="form-textarea" rows="4"></textarea></div>
                        <div class="form-group full"><label class="form-label">3. Significance of the Activity</label><textarea name="significance" class="form-textarea" rows="4"></textarea></div>
                        <div class="form-group full"><label class="form-label">4. Activity Details</label><textarea name="activity_details" class="form-textarea" rows="6" placeholder="Describe what happened, who participated, and what was accomplished." required></textarea></div>
                        <div class="form-group full"><label class="form-label">5. Issues and Challenges</label><textarea name="issues_challenges" class="form-textarea" rows="4" placeholder="Write None if there were no issues."></textarea></div>
                        <div class="form-group full"><label class="form-label">6. Discussion and Assessment</label><textarea name="discussion_assessment" class="form-textarea" rows="5"></textarea></div>
                        <div class="form-group full"><label class="form-label">Recommendations</label><textarea name="recommendations" class="form-textarea" rows="4" placeholder="Write None if there are no recommendations."></textarea></div>
                        <div class="form-section-label">Pictures and digital signature</div>
                        <div class="form-group full"><label class="form-label">Activity Pictures</label><input type="file" name="proof_images[]" class="form-input" multiple accept=".jpg,.jpeg,.png,image/jpeg,image/png"><small class="table-muted">Optional JPG or PNG images. Up to 20 images and 25 MB combined. Pictures are added to Annex A.</small></div>
                        <div class="form-group full"><label class="form-label">Requested Approver</label><select name="approved_by_station_officer_id" class="form-select" data-station-ref="officers"><option value="">Select approving officer</option></select><small class="table-muted">This requests review; it does not approve the AAR.</small></div>
                        <div class="form-group full"><label class="form-label">Electronic Signature</label><input name="signature_name" class="form-input" value="{{ auth()->user()->full_name }}" required><label style="display:flex;gap:.65rem;align-items:flex-start;margin-top:.7rem"><input type="checkbox" name="signature_consent" value="1" required><span class="table-muted">I certify that this AAR is accurate and adopt the typed name above as my electronic signature.</span></label></div>
                    </div>
                </div>
                <div class="report-modal-footer"><button type="button" class="btn btn-outline" data-close-report-modal="aar">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit AAR</button></div>
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
                            <label class="form-label">Progress Report Type</label>
                            <div class="form-helper">Choose one report, or start a two-report sequence.</div>
                            <select name="report_stage" class="form-select" required>
                                <option value="single">Progress Report (one report)</option>
                                <option value="first">1st Progress Report (2nd will follow)</option>
                                <option value="second">2nd Progress Report</option>
                            </select>
                            <input type="hidden" name="parent_progress_investigation_id">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Source Spot Report</label>
                            <div class="form-helper" data-progress-source-help>Select the spot report this progress report continues.</div>
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


    <script>
        window.firefighterMyStationConfig = {
            csrfToken: @json(csrf_token()),
            activeReportTab: @json($activeReportTab),
            currentOfficerName: @json($currentOfficerName),
            currentOfficer: @json($currentOfficerPayload),
            stationId: @json($stationId),
            readinessRefreshIntervalMs: 30000,
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
                aarCreate: @json(route('firefighter.after-activity-reports.store')),
                aarUpdateTemplate: @json(route('firefighter.after-activity-reports.update', ['afterActivityReport' => '__AAR_REPORT_ID__'])),
                aarDeleteTemplate: @json(route('firefighter.after-activity-reports.destroy', ['afterActivityReport' => '__AAR_REPORT_ID__'])),
                reportManageBase: '/api',
                proofImageDeleteTemplate: @json($proofImageDeleteTemplate),
                proofImageOrderTemplate: @json($proofImageOrderTemplate),
                sirTemplate: @json(route('firefighter.spot-reports.sir.word', ['spotReportId' => '__SPOT_REPORT_ID__'])),
                pirTemplate: @json(route('firefighter.progress-reports.pir.word', ['progressReportId' => '__PROGRESS_REPORT_ID__'])),
                firTemplate: @json(route('firefighter.final-reports.fir.word', ['finalReportId' => '__FINAL_REPORT_ID__'])),
                aarTemplate: @json(route('firefighter.after-activity-reports.word', ['afterActivityReport' => '__AAR_REPORT_ID__'])),
                aarPdfTemplate: @json(route('firefighter.after-activity-reports.pdf', ['afterActivityReport' => '__AAR_REPORT_ID__'])),
            },
            finalSubstantiatingDocumentOptions: @json($finalSubstantiatingDocumentOptions),
        };
    </script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite('resources/js/firefighter/station-shell.js')
    </div>
    @include('partials.pwa-body')
</body>
</html>
