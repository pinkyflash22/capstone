@extends('barangay.layouts.app')

@section('active_nav', 'reports')
@section('page_title', 'Reports & Analytics')
@section('page_subtitle', 'Structured reporting view for local risk, actions, and fire history')
@section('hero_title', 'Summarize the barangay record without leaving the operational context.')
@section('hero_copy', 'This reporting page packages the same data used in day-to-day coordination into an easier reading format for barangay leadership, meetings, and follow-up planning.')

@section('hero_badges')
    <span class="hero-badge"><i class="fa-solid fa-file-lines"></i> Report-ready summaries</span>
    <span class="hero-badge"><i class="fa-solid fa-chart-pie"></i> Risk analytics</span>
    <span class="hero-badge"><i class="fa-solid fa-clock-rotate-left"></i> Historical review</span>
@endsection

@section('content')
    @if (session('success'))
        <div class="panel-card rounded-3xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="panel-card rounded-3xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Unable to submit the fire report.</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="metric-grid">
        <article class="metric-card">
            <div class="metric-label">Hazard Classes</div>
            <div class="metric-value">{{ count(array_filter($hazardMix)) }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Checklist Statuses</div>
            <div class="metric-value">{{ count(array_filter($statusMix)) }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Recent Posts</div>
            <div class="metric-value">{{ $latestPosts->count() }}</div>
        </article>
        <article class="metric-card">
            <div class="metric-label">Mapped Points</div>
            <div class="metric-value">{{ $markers->count() }}</div>
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Barangay Fire Reporting</div>
            <h3 class="panel-title">Submit a local fire report</h3>
            <p class="panel-copy">This creates a live fire marker, opens a system incident entry, and records the event under the barangay fire register when your hall is linked.</p>

            <form method="POST" action="{{ route('barangay.reports.fire.store') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="fire_report_title" class="block text-sm font-medium text-slate-700 mb-1">Incident Title</label>
                        <input id="fire_report_title" name="title" type="text" value="{{ old('title') }}" class="w-full border rounded-lg px-3 py-2" placeholder="Example: Residential fire near covered court">
                    </div>
                    <div>
                        <label for="fire_report_incident_date" class="block text-sm font-medium text-slate-700 mb-1">Incident Date</label>
                        <input id="fire_report_incident_date" name="incident_date" type="date" value="{{ old('incident_date', now()->toDateString()) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="fire_report_incident_count" class="block text-sm font-medium text-slate-700 mb-1">Affected Incidents Count</label>
                        <input id="fire_report_incident_count" name="incident_count" type="number" min="1" max="100" value="{{ old('incident_count', 1) }}" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label for="fire_report_classification" class="block text-sm font-medium text-slate-700 mb-1">Classification</label>
                        <select id="fire_report_classification" name="fire_classification" class="w-full border rounded-lg px-3 py-2">
                            <option value="accidental" @selected(old('fire_classification') === 'accidental')>Accidental</option>
                            <option value="intentional" @selected(old('fire_classification') === 'intentional')>Intentional</option>
                            <option value="undetermined" @selected(old('fire_classification', 'undetermined') === 'undetermined')>Undetermined</option>
                        </select>
                    </div>
                    <div>
                        <label for="fire_report_cause" class="block text-sm font-medium text-slate-700 mb-1">Suspected Cause</label>
                        <input id="fire_report_cause" name="fire_cause" type="text" value="{{ old('fire_cause') }}" class="w-full border rounded-lg px-3 py-2" placeholder="Electrical wiring, open flame, etc.">
                    </div>
                    <div class="md:col-span-2">
                        <label for="fire_report_location_description" class="block text-sm font-medium text-slate-700 mb-1">Location Description</label>
                        <textarea id="fire_report_location_description" name="location_description" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Street, landmark, purok, nearby structure, access notes">{{ old('location_description') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label for="fire_report_notes" class="block text-sm font-medium text-slate-700 mb-1">Field Notes</label>
                        <textarea id="fire_report_notes" name="notes" rows="4" class="w-full border rounded-lg px-3 py-2" placeholder="Visible spread, trapped access, responders informed, immediate needs">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="map-card">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                        <div>
                            <div class="tiny-label">Location Picker</div>
                            <p class="panel-copy !mt-1">Click the map to place the reported fire, or use your current location to prefill the coordinates.</p>
                        </div>
                        <button type="button" id="useCurrentLocationBtn" class="bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600 transition">
                            Use My Current Location
                        </button>
                    </div>
                    <div id="barangayFireReportMap" style="height: 320px; border-radius: 18px; overflow: hidden; border: 1px solid rgba(226,232,240,0.92);"></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label for="fire_report_lat" class="block text-sm font-medium text-slate-700 mb-1">Latitude</label>
                            <input id="fire_report_lat" name="lat" type="text" value="{{ old('lat') }}" class="w-full border rounded-lg px-3 py-2" placeholder="15.8222000" readonly>
                        </div>
                        <div>
                            <label for="fire_report_lng" class="block text-sm font-medium text-slate-700 mb-1">Longitude</label>
                            <input id="fire_report_lng" name="lng" type="text" value="{{ old('lng') }}" class="w-full border rounded-lg px-3 py-2" placeholder="120.3297000" readonly>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition">
                        Submit Fire Report
                    </button>
                </div>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Incident Register</div>
            <h3 class="panel-title">Historical incident snapshot</h3>
            @if($historicalIncidents->isEmpty())
                <div class="empty-state">No historical incidents are available for reporting yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Cause</th>
                                <th>Damage</th>
                                <th>Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($historicalIncidents->take(8) as $incident)
                                <tr>
                                    <td>{{ optional($incident->incident_date)->format('M d, Y') }}</td>
                                    <td>{{ $incident->fire_cause ?? 'Pending' }}</td>
                                    <td>{{ $incident->estimated_damage ? number_format((float) $incident->estimated_damage, 2) : 'N/A' }}</td>
                                    <td>{{ $incident->is_verified ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>

    <section class="panel-grid two">
        <article class="panel-card">
            <div class="panel-eyebrow">Barangay Fire Register</div>
            <h3 class="panel-title">Latest fire records submitted from the barangay side</h3>
            @if($fireRecords->isEmpty())
                <div class="empty-state">No barangay fire records are linked to this barangay hall yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Classification</th>
                                <th>Cause</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fireRecords->take(8) as $record)
                                <tr>
                                    <td>{{ optional($record->incident_date)->format('M d, Y') }}</td>
                                    <td>{{ ucfirst($record->fire_classification ?? 'undetermined') }}</td>
                                    <td>{{ $record->fire_cause ?? 'Pending assessment' }}</td>
                                    <td>{{ $record->incident_count ?? 1 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>

        <article class="panel-card">
            <div class="panel-eyebrow">Action Register</div>
            <h3 class="panel-title">Checklist and mitigation reporting</h3>
            @if($predictionChecklists->isEmpty())
                <div class="empty-state">No checklist analytics are available for this barangay yet.</div>
            @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($predictionChecklists->take(8) as $item)
                                <tr>
                                    <td>{{ $item->title }}</td>
                                    <td>{{ strtoupper($item->priority_level) }}</td>
                                    <td>{{ str_replace('_', ' ', strtoupper($item->status)) }}</td>
                                    <td>{{ optional($item->target_date)->format('M d, Y') ?? 'No target' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    </section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mapElement = document.getElementById('barangayFireReportMap');
        const latInput = document.getElementById('fire_report_lat');
        const lngInput = document.getElementById('fire_report_lng');
        const useCurrentLocationBtn = document.getElementById('useCurrentLocationBtn');

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
