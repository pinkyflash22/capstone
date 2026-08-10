@extends('barangay.layouts.app')

@section('active_nav', 'risk-map')
@section('page_title', 'Map')
@section('page_subtitle', 'Layered barangay map for hazards, risks, incidents, and local resources')
@section('hero_title', 'Use one map view for hazards, risk markers, and response resources.')
@section('hero_copy', 'This page mirrors the system’s mapping context by surfacing relevant markers and hazard-linked points that can affect patrol routing and early warning decisions.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> Marker inventory</span>
    <span class="hero-badge"><i class="fa-solid fa-school"></i> Exposure points</span>
    <span class="hero-badge"><i class="fa-solid fa-water"></i> Resource awareness</span>
@endsection

@php
    $riskAreaMapPayload = $riskAreas->map(function ($riskArea) {
        return [
            'id' => $riskArea->risk_area_id,
            'title' => $riskArea->marker?->title,
            'description' => $riskArea->marker?->description,
            'lat' => $riskArea->marker?->lat,
            'lng' => $riskArea->marker?->lng,
            'status' => $riskArea->status,
            'risk_type' => $riskArea->risk_type,
            'hazard_type' => $riskArea->hazard_type,
            'assessment_level' => $riskArea->assessment_level,
            'risk_score' => $riskArea->risk_score,
            'street_or_sitio' => $riskArea->street_or_sitio,
            'station_name' => $riskArea->station?->station_name,
        ];
    })->values();

    $fallbackMarkerMapPayload = collect($markers)->map(function ($marker) {
        return [
            'marker_id' => $marker->marker_id,
            'title' => $marker->title,
            'description' => $marker->description,
            'lat' => $marker->lat,
            'lng' => $marker->lng,
            'category' => $marker->category,
            'status' => $marker->status,
        ];
    })->values();

    $hallMapPayload = $hall && $hall->marker ? [
        'title' => $hall->hall_name ?: 'Barangay Hall',
        'description' => $hall->address?->street ?: ($barangay?->barangay_name ?: 'Barangay hall'),
        'lat' => $hall->marker->lat,
        'lng' => $hall->marker->lng,
        'category' => 'barangay',
    ] : null;
@endphp

@push('styles')
<style>
    .barangay-risk-form {
        display: grid;
        gap: 1rem;
    }

    .barangay-risk-step {
        border-radius: 24px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(248, 250, 252, 0.92) 100%);
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .barangay-risk-step[open] {
        border-color: rgba(251, 191, 36, 0.22);
    }

    .barangay-risk-step > summary {
        list-style: none;
        padding: 1rem;
    }

    .barangay-risk-step > summary::-webkit-details-marker {
        display: none;
    }

    .barangay-risk-step__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.9rem;
    }

    .barangay-risk-step__body {
        padding: 0 1rem 1rem;
    }

    .barangay-risk-step__status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 0 0.8rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .barangay-risk-step__status--required {
        background: #fff7ed;
        color: #c2410c;
    }

    .barangay-risk-step__status--review {
        background: #f8fafc;
        color: #475569;
    }

    .barangay-risk-step__status--optional {
        background: #ecfdf5;
        color: #047857;
    }

    .barangay-risk-step__status--map {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .barangay-risk-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.95rem;
    }

    .barangay-risk-field--full {
        grid-column: 1 / -1;
    }

    .barangay-risk-field label {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
        color: #334155;
        font-size: 0.88rem;
        font-weight: 700;
    }

    .barangay-risk-field__hint {
        margin-top: 0.45rem;
        color: #64748b;
        font-size: 0.76rem;
        line-height: 1.5;
    }

    .barangay-risk-field__required {
        color: #c2410c;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .barangay-risk-input,
    .barangay-risk-select,
    .barangay-risk-textarea,
    .barangay-risk-file {
        width: 100%;
        min-height: 46px;
        border-radius: 16px;
        border: 1px solid rgba(203, 213, 225, 0.95);
        background: #fff;
        padding: 0.85rem 0.95rem;
        color: #0f172a;
        font-size: 0.92rem;
    }

    .barangay-risk-textarea {
        min-height: 112px;
        resize: vertical;
    }

    .barangay-risk-chip-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .barangay-risk-chip {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        min-height: 48px;
        padding: 0.8rem 0.95rem;
        border-radius: 18px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: #fff;
        color: #334155;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .barangay-risk-chip input {
        width: 16px;
        height: 16px;
        accent-color: #ea580c;
        flex-shrink: 0;
    }

    .barangay-risk-map-panel {
        border-radius: 22px;
        border: 1px solid rgba(226, 232, 240, 0.92);
        background: rgba(255, 255, 255, 0.88);
        padding: 1rem;
    }

    .barangay-risk-map-panel__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .barangay-risk-location-btn,
    .barangay-risk-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 46px;
        border-radius: 16px;
        font-size: 0.9rem;
        font-weight: 800;
        transition: all 0.2s ease;
    }

    .barangay-risk-location-btn {
        padding: 0 1rem;
        border: 1px solid rgba(251, 191, 36, 0.22);
        background: #f59e0b;
        color: #fff;
        box-shadow: 0 12px 24px rgba(245, 158, 11, 0.18);
    }

    .barangay-risk-submit {
        padding: 0 1.2rem;
        border: 0;
        background: linear-gradient(135deg, #ea580c 0%, #dc2626 100%);
        color: #fff;
        box-shadow: 0 14px 28px rgba(234, 88, 12, 0.18);
    }

    .barangay-risk-review {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        border-radius: 22px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background: linear-gradient(180deg, rgba(255, 247, 237, 0.84) 0%, rgba(255, 255, 255, 0.96) 100%);
        padding: 1rem;
    }

    .barangay-risk-review__list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .barangay-risk-review__item {
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(251, 191, 36, 0.12);
        padding: 0.85rem;
    }

    .barangay-risk-review__item span {
        display: block;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .barangay-risk-review__item strong {
        display: block;
        margin-top: 0.45rem;
        color: #0f172a;
        font-size: 0.95rem;
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        .barangay-risk-step {
            border-radius: 22px;
            margin-inline: -2px;
        }

        .barangay-risk-step > summary,
        .barangay-risk-step__body,
        .barangay-risk-map-panel,
        .barangay-risk-review {
            padding-left: 0.9rem;
            padding-right: 0.9rem;
        }

        .barangay-risk-step__head {
            flex-direction: column;
            align-items: flex-start;
        }

        .barangay-risk-grid,
        .barangay-risk-chip-grid,
        .barangay-risk-review__list {
            grid-template-columns: 1fr;
        }

        .barangay-risk-map-panel__head {
            align-items: stretch;
        }

        .barangay-risk-location-btn,
        .barangay-risk-submit {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    @if (session('success'))
        <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Unable to submit the risk marker.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="panel-eyebrow">Operational Map</div>
                <h3 class="panel-title">Barangay monitoring layers</h3>
                <p class="panel-copy">The map is shown first so barangay officers can immediately see live incidents, verified risk markers, hazard-related points, hydrants and other resource references, barangay halls, and fire stations in one working view.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:min-w-[320px]">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="tiny-label">Live</div>
                    <div id="barangayMapLiveCount" class="summary-value !mt-1">0</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="tiny-label">Incidents</div>
                    <div id="barangayMapIncidentCount" class="summary-value !mt-1">0</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="tiny-label">Risk Markers</div>
                    <div id="barangayMapRiskCount" class="summary-value !mt-1">{{ $riskAreas->count() }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="tiny-label">Last Updated</div>
                    <div id="barangayMapUpdatedAt" class="text-sm font-bold text-slate-700 mt-2">Loading...</div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="rounded-[26px] border border-slate-200 bg-white p-3 shadow-sm">
                <div id="barangayOperationsMap" style="height: 560px; border-radius: 20px; overflow: hidden;"></div>
            </div>
            <aside class="rounded-[26px] border border-slate-200 bg-slate-50/70 p-5">
                <div class="tiny-label">Layer Guide</div>
                <div class="list-stack !mt-4">
                    <div class="list-item">
                        <h4>Live</h4>
                        <p>Active incidents that are still operational. Uncheck this layer if you do not want live markers displayed.</p>
                    </div>
                    <div class="list-item">
                        <h4>Incidents</h4>
                        <p>Recent incident markers, including already controlled or closed events returned by the incident feed.</p>
                    </div>
                    <div class="list-item">
                        <h4>Risk, Hazard, Resources</h4>
                        <p>Risk markers come from barangay submissions. Hazard and resource points come from mapped system markers such as hydrants, schools, bridges, and other support references.</p>
                    </div>
                    <div class="list-item">
                        <h4>Barangay and Station</h4>
                        <p>These layers help officers see barangay hall references and station locations while checking local fire exposure.</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Risk Submission</div>
            <h3 class="panel-title">Mark a local risk area</h3>
            <div class="map-card">
                <p class="panel-copy">Create a mapped local risk marker for congestion, wiring, flammable buildup, or other recurring field hazards. This page serves as the barangay map workspace, where risk entries can later be reviewed alongside other layers such as incidents, hazards, and response resources.</p>

                <form method="POST" action="{{ route('barangay.risk-map.store') }}" class="barangay-risk-form mt-6" enctype="multipart/form-data">
                    @csrf
                    <details class="barangay-risk-step" open>
                        <summary>
                            <div class="barangay-risk-step__head">
                                <div>
                                <div class="tiny-label">Step 1</div>
                                <h4 class="mt-2 text-base font-extrabold text-slate-900">Basic information</h4>
                                <p class="mt-1 text-sm text-slate-500">Add the title, area description, and the main local reference first.</p>
                                </div>
                                <span class="barangay-risk-step__status barangay-risk-step__status--required">Required</span>
                            </div>
                        </summary>
                        <div class="barangay-risk-step__body">
                            <div class="barangay-risk-grid">
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_title">Risk Marker Title <span class="barangay-risk-field__required">Required</span></label>
                                <input id="risk_area_title" name="title" type="text" value="{{ old('title') }}" class="barangay-risk-input" placeholder="Example: Dense light-material housing near creek">
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-signature text-orange-500 mr-1"></i>Use a short area name officers will recognize quickly.</p>
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_location_description">Location Description <span class="barangay-risk-field__required">Required</span></label>
                                <textarea id="risk_area_location_description" name="location_description" rows="3" class="barangay-risk-textarea" placeholder="Purok, street, landmark, access constraints">{{ old('location_description') }}</textarea>
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-location-dot text-orange-500 mr-1"></i>Say what responders should look for when approaching the area.</p>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_street">Street / Purok / Sitio</label>
                                <input id="risk_area_street" name="street_or_sitio" type="text" value="{{ old('street_or_sitio') }}" class="barangay-risk-input" placeholder="Specific local reference">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_station_id">Linked Station</label>
                                <select id="risk_area_station_id" name="station_id" class="barangay-risk-select">
                                    <option value="">No linked station</option>
                                    @foreach ($stations as $station)
                                        <option value="{{ $station->station_id }}" @selected((string) old('station_id') === (string) $station->station_id)>{{ $station->station_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            </div>
                        </div>
                    </details>

                    <details class="barangay-risk-step" open>
                        <summary>
                            <div class="barangay-risk-step__head">
                                <div>
                                <div class="tiny-label">Step 2</div>
                                <h4 class="mt-2 text-base font-extrabold text-slate-900">Risk profile</h4>
                                <p class="mt-1 text-sm text-slate-500">Capture the exposure type, density, access, and nearby support conditions.</p>
                                </div>
                                <span class="barangay-risk-step__status barangay-risk-step__status--review">Review-ready</span>
                            </div>
                        </summary>
                        <div class="barangay-risk-step__body">
                        <div class="barangay-risk-grid">
                            <div class="barangay-risk-field">
                                <label for="risk_area_type">Risk Type</label>
                                <select id="risk_area_type" name="risk_type" class="barangay-risk-select">
                                    <option value="">Select risk type</option>
                                    @foreach ([
                                        'electrical_hazard' => 'Electrical hazard',
                                        'overcrowded_area' => 'Overcrowded area',
                                        'narrow_road_access' => 'Narrow road access',
                                        'informal_settlement' => 'Informal settlement',
                                        'cooking_fire_prone_activity' => 'Cooking / fire-prone activity',
                                        'blocked_hydrant' => 'Blocked hydrant',
                                        'combustible_materials' => 'Combustible materials',
                                        'poor_waste_disposal' => 'Poor waste disposal',
                                        'previous_fire_prone_location' => 'Previous fire-prone location',
                                    ] as $riskTypeValue => $riskTypeLabel)
                                        <option value="{{ $riskTypeValue }}" @selected(old('risk_type') === $riskTypeValue)>{{ $riskTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_factor">Risk Factor</label>
                                <select id="risk_area_factor" name="risk_factor" class="barangay-risk-select">
                                    <option value="people_density" @selected(old('risk_factor') === 'people_density')>People Density</option>
                                    <option value="housing_material" @selected(old('risk_factor') === 'housing_material')>Housing Material</option>
                                    <option value="housing_spacing" @selected(old('risk_factor') === 'housing_spacing')>Housing Spacing</option>
                                    <option value="electrical_risk" @selected(old('risk_factor') === 'electrical_risk')>Electrical Risk</option>
                                    <option value="wiring" @selected(old('risk_factor') === 'wiring')>Wiring</option>
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_mode">Condition / Mode</label>
                                <select id="risk_area_mode" name="mode" class="barangay-risk-select">
                                    @foreach (['low', 'medium', 'high', 'concrete', 'mixed', 'light_materials', 'dikit_dikit', 'nearly_dikit_dikit', 'moderate', 'far', 'none', 'octopus', 'straight', 'overflow'] as $modeOption)
                                        <option value="{{ $modeOption }}" @selected(old('mode') === $modeOption)>{{ str_replace('_', ' ', ucfirst($modeOption)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_hazard_type">Hazard Type</label>
                                <select id="risk_area_hazard_type" name="hazard_type" class="barangay-risk-select">
                                    <option value="overcrowding" @selected(old('hazard_type') === 'overcrowding')>Overcrowding</option>
                                    <option value="electrical" @selected(old('hazard_type') === 'electrical')>Electrical</option>
                                    <option value="flammable" @selected(old('hazard_type') === 'flammable')>Flammable</option>
                                    <option value="none" @selected(old('hazard_type', 'none') === 'none')>None / General</option>
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_population_density_level">Population Density</label>
                                <select id="risk_area_population_density_level" name="population_density_level" class="barangay-risk-select">
                                    <option value="">Select level</option>
                                    @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('population_density_level') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_building_density_level">Building Density</label>
                                <select id="risk_area_building_density_level" name="building_density_level" class="barangay-risk-select">
                                    <option value="">Select level</option>
                                    @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('building_density_level') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_road_accessibility">Road Accessibility</label>
                                <select id="risk_area_road_accessibility" name="road_accessibility" class="barangay-risk-select">
                                    <option value="">Select accessibility</option>
                                    @foreach (['easy' => 'Easy', 'moderate' => 'Moderate', 'difficult' => 'Difficult', 'very_difficult' => 'Very Difficult'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('road_accessibility') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_assessment_level">Assessment Level</label>
                                <select id="risk_area_assessment_level" name="assessment_level" class="barangay-risk-select">
                                    <option value="">Select level</option>
                                    @foreach (['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('assessment_level') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_status">Status</label>
                                <select id="risk_area_status" name="status" class="barangay-risk-select">
                                    <option value="pending" @selected(old('status', 'pending') === 'pending')>Pending</option>
                                    <option value="verified" @selected(old('status') === 'verified')>Verified</option>
                                    <option value="resolved" @selected(old('status') === 'resolved')>Resolved</option>
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_risk_score">Risk Score</label>
                                <input id="risk_area_risk_score" name="risk_score" type="number" step="0.01" min="0" max="100" value="{{ old('risk_score') }}" class="barangay-risk-input" placeholder="0 to 100">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_radius">Estimated Radius (m)</label>
                                <input id="risk_area_radius" name="estimated_radius" type="number" step="0.01" min="0" max="10000" value="{{ old('estimated_radius') }}" class="barangay-risk-input" placeholder="Affected radius">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_distance_to_hydrant">Distance to Hydrant (m)</label>
                                <input id="risk_area_distance_to_hydrant" name="distance_to_hydrant" type="number" step="0.01" min="0" max="50000" value="{{ old('distance_to_hydrant') }}" class="barangay-risk-input" placeholder="Approximate distance">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_distance_to_water_source">Distance to Water Source (m)</label>
                                <input id="risk_area_distance_to_water_source" name="distance_to_water_source" type="number" step="0.01" min="0" max="50000" value="{{ old('distance_to_water_source') }}" class="barangay-risk-input" placeholder="Approximate distance">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_estimated_affected_households">Affected Households</label>
                                <input id="risk_area_estimated_affected_households" name="estimated_affected_households" type="number" min="0" max="100000" value="{{ old('estimated_affected_households') }}" class="barangay-risk-input" placeholder="Household count">
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_nearby_establishments">Nearby Establishments</label>
                                <textarea id="risk_area_nearby_establishments" name="nearby_establishments" rows="2" class="barangay-risk-textarea" placeholder="Schools, markets, stores, gas outlets">{{ old('nearby_establishments') }}</textarea>
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_assessment_description">Assessment Description</label>
                                <textarea id="risk_area_assessment_description" name="assessment_description" rows="3" class="barangay-risk-textarea" placeholder="Why this area is treated at the selected level">{{ old('assessment_description') }}</textarea>
                            </div>
                        </div>
                        </div>
                    </details>

                    <details class="barangay-risk-step" open>
                        <summary>
                            <div class="barangay-risk-step__head">
                                <div>
                                <div class="tiny-label">Step 3</div>
                                <h4 class="mt-2 text-base font-extrabold text-slate-900">Evidence and supporting details</h4>
                                <p class="mt-1 text-sm text-slate-500">Add site notes, photos, and support condition tags.</p>
                                </div>
                                <span class="barangay-risk-step__status barangay-risk-step__status--optional">Optional</span>
                            </div>
                        </summary>
                        <div class="barangay-risk-step__body">
                        <div class="barangay-risk-grid">
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_remarks">Remarks</label>
                                <textarea id="risk_area_remarks" name="remarks" rows="3" class="barangay-risk-textarea" placeholder="Operational remarks, follow-ups, or field reminders">{{ old('remarks') }}</textarea>
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_notes">Notes</label>
                                <textarea id="risk_area_notes" name="notes" rows="3" class="barangay-risk-textarea" placeholder="Observed materials, blocked access, exposed wiring">{{ old('notes') }}</textarea>
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_photos">Photo Evidence</label>
                                <input id="risk_area_photos" name="photos[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp" class="barangay-risk-file bg-white">
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-camera text-orange-500 mr-1"></i>Optional: upload 1 to 3 clear photos, with a combined total of up to 5 MB.</p>
                                <div id="barangayRiskPhotoUploadStatus" class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600" aria-live="polite">
                                    No photos selected yet. You can save without photos, or add up to 3 images.
                                </div>
                            </div>
                        </div>

                        <div class="barangay-risk-chip-grid mt-4">
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="near_hydrant" value="1" @checked(old('near_hydrant'))> Near hydrant
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="near_water_source" value="1" @checked(old('near_water_source'))> Near water source
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="blocked_hydrant" value="1" @checked(old('blocked_hydrant'))> Blocked hydrant
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="informal_settlement" value="1" @checked(old('informal_settlement'))> Informal settlement
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="cooking_fire_prone_activity" value="1" @checked(old('cooking_fire_prone_activity'))> Cooking / fire-prone activity
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="combustible_materials_present" value="1" @checked(old('combustible_materials_present'))> Combustible materials present
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="poor_waste_disposal" value="1" @checked(old('poor_waste_disposal'))> Poor waste disposal
                            </label>
                            <label class="barangay-risk-chip">
                                <input type="checkbox" name="previous_fire_prone_location" value="1" @checked(old('previous_fire_prone_location'))> Previous fire-prone location
                            </label>
                        </div>
                        </div>
                    </details>

                    <details class="barangay-risk-step" open>
                        <summary>
                            <div class="barangay-risk-step__head">
                                <div>
                                <div class="tiny-label">Step 4</div>
                                <h4 class="mt-2 text-base font-extrabold text-slate-900">Map location and review</h4>
                                <p class="mt-1 text-sm text-slate-500">Pin the area and confirm the location before saving.</p>
                                </div>
                                <span class="barangay-risk-step__status barangay-risk-step__status--map">Map required</span>
                            </div>
                        </summary>
                        <div class="barangay-risk-step__body">
                        <div class="barangay-risk-map-panel">
                            <div class="barangay-risk-map-panel__head">
                                <div>
                                    <div class="tiny-label">Map Picker</div>
                                    <p class="panel-copy !mt-1">Tap the map to place the marker or start from your current GPS point.</p>
                                </div>
                                <button type="button" id="useRiskCurrentLocationBtn" class="barangay-risk-location-btn">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                    Use My Current Location
                                </button>
                            </div>
                            <div id="barangayRiskAreaMap" style="height: 320px; border-radius: 18px; overflow: hidden; border: 1px solid rgba(226,232,240,0.92);"></div>
                            <div class="barangay-risk-grid mt-4">
                                <div class="barangay-risk-field">
                                    <label for="risk_area_lat">Latitude</label>
                                    <input id="risk_area_lat" name="lat" type="text" value="{{ old('lat') }}" class="barangay-risk-input" readonly>
                                </div>
                                <div class="barangay-risk-field">
                                    <label for="risk_area_lng">Longitude</label>
                                    <input id="risk_area_lng" name="lng" type="text" value="{{ old('lng') }}" class="barangay-risk-input" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="barangay-risk-review mt-4">
                            <div>
                                <div class="tiny-label">Review before submit</div>
                                <p class="panel-copy !mt-1">Check the pin, category, and field notes. The record goes to verification after saving.</p>
                            </div>
                            <div class="barangay-risk-review__list">
                                <div class="barangay-risk-review__item">
                                    <span>Risk type</span>
                                    <strong>Set from Step 2</strong>
                                </div>
                                <div class="barangay-risk-review__item">
                                    <span>Pin</span>
                                    <strong>Latitude and longitude captured above</strong>
                                </div>
                                <div class="barangay-risk-review__item">
                                    <span>Photos</span>
                                    <strong>Optional evidence kept with the record</strong>
                                </div>
                            </div>
                            <button type="submit" id="barangayRiskSubmitButton" class="barangay-risk-submit">
                                <i class="fa-solid fa-floppy-disk"></i>
                                Save Risk Marker
                            </button>
                        </div>
                        </div>
                    </details>
                </form>
            </div>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Risk Feed</div>
            <h3 class="panel-title">Latest barangay risk markers</h3>
            @if($riskAreas->isEmpty())
                <div class="empty-state">No barangay risk markers are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($riskAreas->take(7) as $riskArea)
                        <div class="list-item">
                            <h4>{{ $riskArea->marker?->title ?? 'Untitled risk marker' }}</h4>
                            <p>{{ $riskArea->marker?->description ?? 'No marker description provided.' }}</p>
                            <div class="tag-row">
                                <span class="tag medium">{{ strtoupper(str_replace('_', ' ', $riskArea->risk_type ?: $riskArea->risk_factor)) }}</span>
                                <span class="tag {{ $riskArea->status }}">{{ strtoupper($riskArea->status) }}</span>
                                <span class="tag low">{{ strtoupper($riskArea->assessment_level ?: $riskArea->hazard_type) }}</span>
                                @if($riskArea->station)
                                    <span class="tag in_progress">{{ $riskArea->station->station_name }}</span>
                                @endif
                                @if($riskArea->photos->isNotEmpty())
                                    <span class="tag complied">{{ $riskArea->photos->count() }} photo{{ $riskArea->photos->count() > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            @if($riskArea->remarks || $riskArea->street_or_sitio)
                                <p class="mt-2 text-sm text-slate-500">
                                    {{ $riskArea->street_or_sitio ?: 'General area' }}
                                    @if($riskArea->remarks)
                                        · {{ $riskArea->remarks }}
                                    @endif
                                </p>
                            @endif
                            @if($riskArea->photos->isNotEmpty())
                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    @foreach($riskArea->photos->take(3) as $photo)
                                        <a href="{{ \App\Support\MediaPath::url($photo->photo_path) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200 bg-white aspect-square">
                                            <img src="{{ \App\Support\MediaPath::url($photo->photo_path) }}" alt="{{ $photo->photo_name ?: 'Risk area photo' }}" class="w-full h-full object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Marker Inventory</div>
            <h3 class="panel-title">Relevant mapped points in context</h3>
            @if($markers->isEmpty())
                <div class="empty-state">No marker records are available yet.</div>
            @else
                <div class="list-stack">
                    @foreach($markers->take(8) as $marker)
                        <div class="list-item">
                            <h4>{{ $marker->title ?? 'Untitled marker' }}</h4>
                            <p>{{ $marker->description ?? 'No marker description provided.' }}</p>
                            <div class="tag-row">
                                <span class="tag low">{{ strtoupper($marker->category ?? 'UNKNOWN') }}</span>
                                <span class="tag {{ $marker->status === 'active' ? 'complied' : 'deferred' }}">{{ strtoupper($marker->status ?? 'UNKNOWN') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Summary</div>
            <h3 class="panel-title">Barangay risk marker totals</h3>
            <div class="list-stack">
                <div class="stat-line">
                    <strong>Total risk markers</strong>
                    <span class="tag high">{{ $portalStats['risk_markers'] ?? $riskAreas->count() }}</span>
                </div>
                <div class="stat-line">
                    <strong>Pending review</strong>
                    <span class="tag pending">{{ $riskAreas->where('status', 'pending')->count() }}</span>
                </div>
                <div class="stat-line">
                    <strong>Verified</strong>
                    <span class="tag complied">{{ $riskAreas->where('status', 'verified')->count() }}</span>
                </div>
                <div class="stat-line">
                    <strong>Resolved</strong>
                    <span class="tag low">{{ $riskAreas->where('status', 'resolved')->count() }}</span>
                </div>
            </div>
        </article>
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof L === 'undefined') {
            return;
        }

        function formatBarangayRiskFileSize(bytes = 0) {
            const normalizedBytes = Number(bytes) || 0;

            if (normalizedBytes >= 1024 * 1024) {
                return `${(normalizedBytes / (1024 * 1024)).toFixed(2)} MB`;
            }

            if (normalizedBytes >= 1024) {
                return `${Math.round(normalizedBytes / 1024)} KB`;
            }

            return `${normalizedBytes} B`;
        }

        function setBarangayRiskPhotoUploadStatus(message, tone = 'neutral') {
            const statusNode = document.getElementById('barangayRiskPhotoUploadStatus');
            if (!statusNode) {
                return;
            }

            statusNode.textContent = message;
            statusNode.classList.remove('border-slate-200', 'bg-slate-50', 'text-slate-600', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-700', 'border-amber-200', 'bg-amber-50', 'text-amber-700', 'border-red-200', 'bg-red-50', 'text-red-700');

            if (tone === 'success') {
                statusNode.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-700');
                return;
            }

            if (tone === 'loading') {
                statusNode.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-700');
                return;
            }

            if (tone === 'error') {
                statusNode.classList.add('border-red-200', 'bg-red-50', 'text-red-700');
                return;
            }

            statusNode.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
        }

        function setBarangayRiskSubmitAvailability(isEnabled) {
            const submitButton = document.getElementById('barangayRiskSubmitButton');
            if (!submitButton) {
                return;
            }

            submitButton.disabled = !isEnabled;
            submitButton.style.opacity = isEnabled ? '' : '0.6';
            submitButton.style.pointerEvents = isEnabled ? '' : 'none';
            submitButton.setAttribute('aria-disabled', isEnabled ? 'false' : 'true');
        }

        function evaluateBarangayRiskPhotoSelection() {
            const maxPhotoCount = 3;
            const maxPhotoTotalBytes = 5 * 1024 * 1024;
            const photoInput = document.getElementById('risk_area_photos');
            const files = Array.from(photoInput?.files || []);

            if (files.length === 0) {
                return {
                    isValid: true,
                    message: 'No photos selected yet. You can save without photos, or add up to 3 images.',
                    tone: 'neutral',
                };
            }

            if (files.length > maxPhotoCount) {
                return {
                    isValid: false,
                    message: 'Too many photos selected. Upload up to 3 images only.',
                    tone: 'error',
                };
            }

            const totalBytes = files.reduce((sum, file) => sum + (Number(file?.size) || 0), 0);

            if (totalBytes > maxPhotoTotalBytes) {
                return {
                    isValid: false,
                    message: `Selected photos total ${formatBarangayRiskFileSize(totalBytes)}. Keep the combined upload within 5.00 MB.`,
                    tone: 'error',
                };
            }

            return {
                isValid: true,
                message: `${files.length} photo(s) ready to upload • ${formatBarangayRiskFileSize(totalBytes)} total`,
                tone: 'success',
            };
        }

        function refreshBarangayRiskPhotoUploadState() {
            setBarangayRiskPhotoUploadStatus('Checking selected photos...', 'loading');

            window.setTimeout(() => {
                const result = evaluateBarangayRiskPhotoSelection();
                setBarangayRiskPhotoUploadStatus(result.message, result.tone);
                setBarangayRiskSubmitAvailability(result.isValid);
            }, 60);
        }

        document.getElementById('risk_area_photos')?.addEventListener('change', refreshBarangayRiskPhotoUploadState);
        refreshBarangayRiskPhotoUploadState();

        const riskAreaRecords = @json($riskAreaMapPayload);
        const fallbackMarkerRecords = @json($fallbackMarkerMapPayload);
        const hallRecord = @json($hallMapPayload);
        const defaultLat = hallRecord && hallRecord.lat !== null && hallRecord.lat !== undefined
            ? Number(hallRecord.lat)
            : 15.8222;
        const defaultLng = hallRecord && hallRecord.lng !== null && hallRecord.lng !== undefined
            ? Number(hallRecord.lng)
            : 120.3297;
        const operationsMapElement = document.getElementById('barangayOperationsMap');
        const liveCountElement = document.getElementById('barangayMapLiveCount');
        const incidentCountElement = document.getElementById('barangayMapIncidentCount');
        const riskCountElement = document.getElementById('barangayMapRiskCount');
        const updatedAtElement = document.getElementById('barangayMapUpdatedAt');

        function buildOperationalIcon(options) {
            const size = options.size || 18;
            const iconHtml = options.iconHtml
                ? `<span style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">${options.iconHtml}</span>`
                : `<i class="fa-solid ${options.icon || 'fa-location-dot'}" style="font-size:${options.fontSize || 8}px;"></i>`;

            return L.divIcon({
                className: 'barangay-operational-marker',
                html: `
                    <div style="
                        width:${size}px;
                        height:${size}px;
                        border-radius:999px;
                        background:${options.color};
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        color:#fff;
                        border:2px solid rgba(255,255,255,0.94);
                        box-shadow:0 0 0 3px rgba(255,255,255,0.92), 0 12px 24px rgba(15,23,42,0.16);
                    ">
                        ${iconHtml}
                    </div>
                `,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2],
                popupAnchor: [0, -(size / 2)],
            });
        }

        function getMarkerVisual(category, options = {}) {
            const normalized = String(category || '').trim().toLowerCase();
            const isLive = options.isLive === true;
            const barangayIcon = '<svg viewBox="0 0 24 24" aria-hidden="true" style="width:11px;height:11px;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;"><circle cx="8" cy="9" r="2"></circle><circle cx="16" cy="9" r="2"></circle><circle cx="12" cy="7" r="2"></circle><path d="M4 18a4 4 0 0 1 8 0"></path><path d="M12 18a4 4 0 0 1 8 0"></path><path d="M8 16a4 4 0 0 1 8 0"></path></svg>';

            if (isLive) {
                return { color: '#dc2626', icon: 'fa-fire-flame-curved', label: 'Live Incident', size: 22, fontSize: 10 };
            }

            switch (normalized) {
                case 'incident':
                case 'fire':
                    return { color: '#ef4444', icon: 'fa-fire', label: 'Incident', size: 20, fontSize: 9 };
                case 'risk':
                    return { color: '#b91c1c', icon: 'fa-triangle-exclamation', label: 'Risk', size: 19, fontSize: 8 };
                case 'hazard':
                    return { color: '#f59e0b', icon: 'fa-circle-exclamation', label: 'Hazard', size: 18, fontSize: 8 };
                case 'hydrant':
                    return { color: '#ef4444', icon: 'fa-fire-extinguisher', label: 'Hydrant', size: 18, fontSize: 8 };
                case 'school':
                    return { color: '#7c3aed', icon: 'fa-school', label: 'Resource', size: 18, fontSize: 8 };
                case 'bridge':
                    return { color: '#475569', icon: 'fa-road-bridge', label: 'Resource', size: 18, fontSize: 8 };
                case 'barangay':
                    return { color: '#0f766e', iconHtml: barangayIcon, label: 'Barangay', size: 19 };
                case 'station':
                    return { color: '#2563eb', icon: 'fa-building', label: 'Station', size: 19, fontSize: 8 };
                default:
                    return { color: '#6b7280', icon: 'fa-map-marker-alt', label: 'Marker', size: 18, fontSize: 8 };
            }
        }

        function buildPopup(title, subtitle, description, extras = []) {
            return `
                <div style="min-width:220px;">
                    <strong style="display:block;color:#0f172a;font-size:0.94rem;">${title || 'Untitled marker'}</strong>
                    ${subtitle ? `<div style="margin-top:6px;color:#64748b;font-size:0.74rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;">${subtitle}</div>` : ''}
                    ${description ? `<p style="margin:8px 0 0;color:#475569;font-size:0.84rem;line-height:1.55;">${description}</p>` : ''}
                    ${extras.filter(Boolean).length ? `<div style="margin-top:10px;color:#64748b;font-size:0.78rem;line-height:1.5;">${extras.filter(Boolean).join('<br>')}</div>` : ''}
                </div>
            `;
        }

        function ensurePane(map, name, zIndex) {
            const pane = map.getPane(name) || map.createPane(name);
            pane.style.zIndex = String(zIndex);
            return name;
        }

        async function initOperationsMap() {
            if (!operationsMapElement) {
                return;
            }

            const map = L.map(operationsMapElement, {
                zoomControl: true,
                scrollWheelZoom: true,
            }).setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            const panes = {
                risk: ensurePane(map, 'barangayRiskPane', 610),
                hazards: ensurePane(map, 'barangayHazardPane', 620),
                barangay: ensurePane(map, 'barangayHallPane', 630),
                stations: ensurePane(map, 'barangayStationPane', 640),
                resources: ensurePane(map, 'barangayResourcePane', 650),
                incidents: ensurePane(map, 'barangayIncidentPane', 660),
                live: ensurePane(map, 'barangayLivePane', 670),
            };

            const liveLayer = L.layerGroup().addTo(map);
            const incidentsLayer = L.layerGroup().addTo(map);
            const hazardsLayer = L.layerGroup().addTo(map);
            const riskLayer = L.layerGroup().addTo(map);
            const resourcesLayer = L.layerGroup().addTo(map);
            const barangayLayer = L.layerGroup().addTo(map);
            const stationsLayer = L.layerGroup().addTo(map);

            const overlays = {
                'Live': liveLayer,
                'Incidents': incidentsLayer,
                'Hazards': hazardsLayer,
                'Risk': riskLayer,
                'Resources': resourcesLayer,
                'Barangay': barangayLayer,
                'Stations': stationsLayer,
            };

            L.control.layers(null, overlays, {
                collapsed: false,
                position: 'topright',
            }).addTo(map);

            const [markersResponse, incidentsResponse] = await Promise.allSettled([
                fetch('/api/markers', { headers: { Accept: 'application/json' } }),
                fetch('/api/recent-incidents', { headers: { Accept: 'application/json' } }),
            ]);

            let apiMarkers = fallbackMarkerRecords;
            let incidents = [];

            if (markersResponse.status === 'fulfilled' && markersResponse.value.ok) {
                const payload = await markersResponse.value.json();
                apiMarkers = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : fallbackMarkerRecords);
            }

            if (incidentsResponse.status === 'fulfilled' && incidentsResponse.value.ok) {
                const payload = await incidentsResponse.value.json();
                incidents = Array.isArray(payload?.data) ? payload.data : [];
            }

            const incidentMarkerIds = new Set(
                incidents
                    .map((incident) => incident?.marker_id)
                    .filter((value) => value !== null && value !== undefined)
                    .map((value) => String(value))
            );

            const bounds = L.latLngBounds();
            const activeStatusIds = new Set([1, 2, 3]);

            incidents.forEach((incident) => {
                const marker = incident?.marker;
                if (!marker || marker.lat === null || marker.lng === null) {
                    return;
                }

                const lat = Number(marker.lat);
                const lng = Number(marker.lng);
                const isLiveIncident = activeStatusIds.has(Number(incident.incident_status_id || 0));
                const incidentMarker = L.marker([lat, lng], {
                    pane: panes.incidents,
                    icon: buildOperationalIcon({
                        ...getMarkerVisual('incident'),
                    }),
                    zIndexOffset: 300,
                }).bindPopup(buildPopup(
                    marker.title || `Incident #${incident.incident_id}`,
                    `Incident · ${incident.incident_status || incident.status || 'Reported'}`,
                    marker.description || 'No incident description provided.',
                    [
                        incident.created_at ? `Reported: ${new Date(incident.created_at).toLocaleString()}` : null,
                        incident.incident_type_name ? `Type: ${incident.incident_type_name}` : null,
                    ]
                ));

                if (!isLiveIncident) {
                    incidentMarker.addTo(incidentsLayer);
                }
                bounds.extend([lat, lng]);

                if (isLiveIncident) {
                    L.marker([lat, lng], {
                        pane: panes.live,
                        icon: buildOperationalIcon({
                            ...getMarkerVisual('incident', { isLive: true }),
                        }),
                        zIndexOffset: 500,
                    }).bindPopup(buildPopup(
                        marker.title || `Incident #${incident.incident_id}`,
                        `Live · ${incident.incident_status || 'Reported'}`,
                        marker.description || 'Live incident marker.',
                        [
                            incident.created_at ? `Reported: ${new Date(incident.created_at).toLocaleString()}` : null,
                            'Uncheck the Live layer to hide active incident markers.',
                        ]
                    )).addTo(liveLayer);
                }
            });

            riskAreaRecords.forEach((riskArea) => {
                if (riskArea.lat === null || riskArea.lng === null) {
                    return;
                }

                const lat = Number(riskArea.lat);
                const lng = Number(riskArea.lng);
                const riskMarker = L.marker([lat, lng], {
                    pane: panes.risk,
                    icon: buildOperationalIcon(getMarkerVisual('risk')),
                    zIndexOffset: 80,
                }).bindPopup(buildPopup(
                    riskArea.title || 'Risk marker',
                    `Risk · ${(riskArea.assessment_level || riskArea.status || 'pending').toString().replace('_', ' ')}`,
                    riskArea.description || 'No risk description provided.',
                    [
                        riskArea.risk_type ? `Type: ${String(riskArea.risk_type).replaceAll('_', ' ')}` : null,
                        riskArea.risk_score !== null && riskArea.risk_score !== undefined ? `Risk score: ${riskArea.risk_score}` : null,
                        riskArea.station_name ? `Linked station: ${riskArea.station_name}` : null,
                    ]
                ));

                riskMarker.addTo(riskLayer);

                const hazardMarker = L.marker([lat, lng], {
                    pane: panes.hazards,
                    icon: buildOperationalIcon(getMarkerVisual('hazard')),
                    zIndexOffset: 120,
                }).bindPopup(buildPopup(
                    riskArea.title || 'Hazard marker',
                    `Hazard · ${(riskArea.hazard_type || 'general').toString().replace('_', ' ')}`,
                    riskArea.description || 'No hazard description provided.',
                    [
                        riskArea.street_or_sitio ? `Area: ${riskArea.street_or_sitio}` : null,
                        riskArea.status ? `Verification: ${riskArea.status}` : null,
                    ]
                ));

                hazardMarker.addTo(hazardsLayer);
                bounds.extend([lat, lng]);
            });

            const seenFallbackMarkerIds = new Set();

            apiMarkers.forEach((marker) => {
                if (!marker || marker.lat === null || marker.lng === null) {
                    return;
                }

                const markerIdKey = marker.marker_id !== null && marker.marker_id !== undefined ? String(marker.marker_id) : null;
                if (markerIdKey && seenFallbackMarkerIds.has(markerIdKey)) {
                    return;
                }

                if (markerIdKey) {
                    seenFallbackMarkerIds.add(markerIdKey);
                }

                const category = String(marker.category || '').trim().toLowerCase();
                const lat = Number(marker.lat);
                const lng = Number(marker.lng);

                if (category === 'risk') {
                    bounds.extend([lat, lng]);
                    return;
                }

                if (incidentMarkerIds.has(String(marker.marker_id)) || category === 'fire') {
                    if (!incidentMarkerIds.has(String(marker.marker_id))) {
                        L.marker([lat, lng], {
                            pane: panes.hazards,
                            icon: buildOperationalIcon(getMarkerVisual('hazard')),
                            zIndexOffset: 120,
                        }).bindPopup(buildPopup(
                            marker.title || 'Hazard point',
                            'Hazard',
                            marker.description || 'Fire-related hazard marker.'
                        )).addTo(hazardsLayer);
                        bounds.extend([lat, lng]);
                    }
                    return;
                }

                if (category === 'station') {
                    L.marker([lat, lng], {
                        pane: panes.stations,
                        icon: buildOperationalIcon(getMarkerVisual('station')),
                        zIndexOffset: 180,
                    }).bindPopup(buildPopup(
                        marker.title || 'Station',
                        'Station',
                        marker.description || 'Fire station marker.'
                    )).addTo(stationsLayer);
                    bounds.extend([lat, lng]);
                    return;
                }

                if (category === 'barangay') {
                    L.marker([lat, lng], {
                        pane: panes.barangay,
                        icon: buildOperationalIcon(getMarkerVisual('barangay')),
                        zIndexOffset: 150,
                    }).bindPopup(buildPopup(
                        marker.title || 'Barangay Hall',
                        'Barangay',
                        marker.description || 'Barangay hall marker.'
                    )).addTo(barangayLayer);
                    bounds.extend([lat, lng]);
                    return;
                }

                if (['hydrant', 'school', 'bridge'].includes(category)) {
                    const visualCategory = category === 'hydrant' ? 'hydrant' : category;
                    L.marker([lat, lng], {
                        pane: panes.resources,
                        icon: buildOperationalIcon(getMarkerVisual(visualCategory)),
                        zIndexOffset: 210,
                    }).bindPopup(buildPopup(
                        marker.title || 'Resource marker',
                        'Resource',
                        marker.description || 'Mapped response resource.'
                    )).addTo(resourcesLayer);
                    bounds.extend([lat, lng]);
                }
            });

            if (hallRecord && hallRecord.lat !== null && hallRecord.lng !== null) {
                L.marker([Number(hallRecord.lat), Number(hallRecord.lng)], {
                    pane: panes.barangay,
                    icon: buildOperationalIcon(getMarkerVisual('barangay')),
                    zIndexOffset: 160,
                }).bindPopup(buildPopup(
                    hallRecord.title || 'Barangay Hall',
                    'Barangay',
                    hallRecord.description || 'Barangay hall reference point.'
                )).addTo(barangayLayer);
                bounds.extend([Number(hallRecord.lat), Number(hallRecord.lng)]);
            }

            if (bounds.isValid()) {
                map.fitBounds(bounds.pad(0.14));
            }

            liveCountElement.textContent = String(liveLayer.getLayers().length);
            incidentCountElement.textContent = String(incidentsLayer.getLayers().length);
            riskCountElement.textContent = String(riskLayer.getLayers().length);
            updatedAtElement.textContent = new Date().toLocaleString([], {
                month: 'short',
                day: '2-digit',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });

            window.setTimeout(() => map.invalidateSize(), 180);
        }

        function initRiskPickerMap() {
            const mapElement = document.getElementById('barangayRiskAreaMap');
            const latInput = document.getElementById('risk_area_lat');
            const lngInput = document.getElementById('risk_area_lng');
            const useCurrentLocationBtn = document.getElementById('useRiskCurrentLocationBtn');

            if (!mapElement || !latInput || !lngInput) {
                return;
            }

            const pickerLat = parseFloat(latInput.value || String(defaultLat));
            const pickerLng = parseFloat(lngInput.value || String(defaultLng));
            const map = L.map(mapElement).setView([pickerLat, pickerLng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            let selectedMarker = null;

            function updateCoordinates(lat, lng) {
                const fixedLat = Number(lat).toFixed(7);
                const fixedLng = Number(lng).toFixed(7);

                latInput.value = fixedLat;
                lngInput.value = fixedLng;

                if (!selectedMarker) {
                    selectedMarker = L.marker([lat, lng]).addTo(map);
                } else {
                    selectedMarker.setLatLng([lat, lng]);
                }
            }

            updateCoordinates(pickerLat, pickerLng);

            map.on('click', function (event) {
                updateCoordinates(event.latlng.lat, event.latlng.lng);
            });

            useCurrentLocationBtn?.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    return;
                }

                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateCoordinates(lat, lng);
                    map.setView([lat, lng], 16, { animate: true });
                });
            });

            window.setTimeout(function () {
                map.invalidateSize();
            }, 120);
        }

        initOperationsMap().catch((error) => {
            console.error('Failed to initialize barangay operations map:', error);
        });
        initRiskPickerMap();
    });
</script>
@endpush
