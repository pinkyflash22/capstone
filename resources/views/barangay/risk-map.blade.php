@extends('barangay.layouts.app')

@section('active_nav', 'risk-map')
@section('page_title', 'Risk Map')
@section('page_subtitle', 'Local marker inventory for area scanning and response context')
@section('hero_title', 'Keep a field view of the places and assets that shape barangay risk.')
@section('hero_copy', 'This page mirrors the system’s mapping context by surfacing relevant markers and hazard-linked points that can affect patrol routing and early warning decisions.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> Marker inventory</span>
    <span class="hero-badge"><i class="fa-solid fa-school"></i> Exposure points</span>
    <span class="hero-badge"><i class="fa-solid fa-water"></i> Resource awareness</span>
@endsection

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

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Risk Submission</div>
            <h3 class="panel-title">Mark a local risk area</h3>
            <div class="map-card">
                <p class="panel-copy">Create a mapped local risk marker for congestion, wiring, flammable buildup, or other recurring field hazards. This writes into the existing `risk_areas` records so it can later be reviewed with BFP and AI checklist workflows.</p>

                <form method="POST" action="{{ route('barangay.risk-map.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="risk_area_title" class="block text-sm font-medium text-slate-700 mb-1">Risk Marker Title</label>
                            <input id="risk_area_title" name="title" type="text" value="{{ old('title') }}" class="w-full border rounded-lg px-3 py-2" placeholder="Example: Dense light-material housing near creek">
                        </div>
                        <div class="md:col-span-2">
                            <label for="risk_area_location_description" class="block text-sm font-medium text-slate-700 mb-1">Location Description</label>
                            <textarea id="risk_area_location_description" name="location_description" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Purok, street, landmark, access constraints">{{ old('location_description') }}</textarea>
                        </div>
                        <div>
                            <label for="risk_area_factor" class="block text-sm font-medium text-slate-700 mb-1">Risk Factor</label>
                            <select id="risk_area_factor" name="risk_factor" class="w-full border rounded-lg px-3 py-2">
                                <option value="people_density" @selected(old('risk_factor') === 'people_density')>People Density</option>
                                <option value="housing_material" @selected(old('risk_factor') === 'housing_material')>Housing Material</option>
                                <option value="housing_spacing" @selected(old('risk_factor') === 'housing_spacing')>Housing Spacing</option>
                                <option value="electrical_risk" @selected(old('risk_factor') === 'electrical_risk')>Electrical Risk</option>
                                <option value="wiring" @selected(old('risk_factor') === 'wiring')>Wiring</option>
                            </select>
                        </div>
                        <div>
                            <label for="risk_area_mode" class="block text-sm font-medium text-slate-700 mb-1">Condition / Mode</label>
                            <select id="risk_area_mode" name="mode" class="w-full border rounded-lg px-3 py-2">
                                @foreach (['low', 'medium', 'high', 'concrete', 'mixed', 'light_materials', 'dikit_dikit', 'nearly_dikit_dikit', 'moderate', 'far', 'none', 'octopus', 'straight', 'overflow'] as $modeOption)
                                    <option value="{{ $modeOption }}" @selected(old('mode') === $modeOption)>{{ str_replace('_', ' ', ucfirst($modeOption)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="risk_area_hazard_type" class="block text-sm font-medium text-slate-700 mb-1">Hazard Type</label>
                            <select id="risk_area_hazard_type" name="hazard_type" class="w-full border rounded-lg px-3 py-2">
                                <option value="overcrowding" @selected(old('hazard_type') === 'overcrowding')>Overcrowding</option>
                                <option value="electrical" @selected(old('hazard_type') === 'electrical')>Electrical</option>
                                <option value="flammable" @selected(old('hazard_type') === 'flammable')>Flammable</option>
                                <option value="none" @selected(old('hazard_type', 'none') === 'none')>None / General</option>
                            </select>
                        </div>
                        <div>
                            <label for="risk_area_status" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                            <select id="risk_area_status" name="status" class="w-full border rounded-lg px-3 py-2">
                                <option value="pending" @selected(old('status', 'pending') === 'pending')>Pending</option>
                                <option value="verified" @selected(old('status') === 'verified')>Verified</option>
                                <option value="resolved" @selected(old('status') === 'resolved')>Resolved</option>
                            </select>
                        </div>
                        <div>
                            <label for="risk_area_station_id" class="block text-sm font-medium text-slate-700 mb-1">Linked Station</label>
                            <select id="risk_area_station_id" name="station_id" class="w-full border rounded-lg px-3 py-2">
                                <option value="">No linked station</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->station_id }}" @selected((string) old('station_id') === (string) $station->station_id)>{{ $station->station_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="risk_area_risk_score" class="block text-sm font-medium text-slate-700 mb-1">Risk Score</label>
                            <input id="risk_area_risk_score" name="risk_score" type="number" step="0.01" min="0" max="100" value="{{ old('risk_score') }}" class="w-full border rounded-lg px-3 py-2" placeholder="0 to 100">
                        </div>
                        <div>
                            <label for="risk_area_radius" class="block text-sm font-medium text-slate-700 mb-1">Estimated Radius (meters)</label>
                            <input id="risk_area_radius" name="estimated_radius" type="number" step="0.01" min="0" max="10000" value="{{ old('estimated_radius') }}" class="w-full border rounded-lg px-3 py-2" placeholder="Affected radius">
                        </div>
                        <div class="md:col-span-2">
                            <label for="risk_area_notes" class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                            <textarea id="risk_area_notes" name="notes" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Observed materials, blocked access, exposed wiring, local concerns">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="near_hydrant" value="1" @checked(old('near_hydrant'))>
                            Near hydrant
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="near_water_source" value="1" @checked(old('near_water_source'))>
                            Near water source
                        </label>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-4 bg-white/80">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <div>
                                <div class="tiny-label">Risk Map Picker</div>
                                <p class="panel-copy !mt-1">Click the map to place the risk marker, or use your current location as the starting point.</p>
                            </div>
                            <button type="button" id="useRiskCurrentLocationBtn" class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600 transition">
                                Use My Current Location
                            </button>
                        </div>
                        <div id="barangayRiskAreaMap" style="height: 320px; border-radius: 18px; overflow: hidden; border: 1px solid rgba(226,232,240,0.92);"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label for="risk_area_lat" class="block text-sm font-medium text-slate-700 mb-1">Latitude</label>
                                <input id="risk_area_lat" name="lat" type="text" value="{{ old('lat') }}" class="w-full border rounded-lg px-3 py-2" readonly>
                            </div>
                            <div>
                                <label for="risk_area_lng" class="block text-sm font-medium text-slate-700 mb-1">Longitude</label>
                                <input id="risk_area_lng" name="lng" type="text" value="{{ old('lng') }}" class="w-full border rounded-lg px-3 py-2" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                            Save Risk Marker
                        </button>
                    </div>
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
                                <span class="tag medium">{{ strtoupper(str_replace('_', ' ', $riskArea->risk_factor)) }}</span>
                                <span class="tag {{ $riskArea->status }}">{{ strtoupper($riskArea->status) }}</span>
                                <span class="tag low">{{ strtoupper($riskArea->hazard_type) }}</span>
                                @if($riskArea->station)
                                    <span class="tag in_progress">{{ $riskArea->station->station_name }}</span>
                                @endif
                            </div>
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
        const mapElement = document.getElementById('barangayRiskAreaMap');
        const latInput = document.getElementById('risk_area_lat');
        const lngInput = document.getElementById('risk_area_lng');
        const useCurrentLocationBtn = document.getElementById('useRiskCurrentLocationBtn');

        if (!mapElement || typeof L === 'undefined' || !latInput || !lngInput) {
            return;
        }

        const defaultLat = parseFloat(latInput.value || '15.8222');
        const defaultLng = parseFloat(lngInput.value || '120.3297');
        const map = L.map(mapElement).setView([defaultLat, defaultLng], 14);

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

        updateCoordinates(defaultLat, defaultLng);

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
    });
</script>
@endpush
