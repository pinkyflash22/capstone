@php
    use Illuminate\Support\Facades\Storage;

    $hall = $barangay->hall;
    $marker = $hall?->marker;
    $officers = $barangay->barangayOfficers->sortBy(fn ($officer) => strtolower((string) $officer->position))->values();
    $fireRecords = $barangay->fireRecords->sortByDesc('incident_date')->values();
    $historicalRecords = $barangay->historicalFireIncidents->sortByDesc('incident_date')->values();
    $hazardRecords = collect($hall?->riskAreas ?? [])->sortByDesc('updated_at')->values();
    $positions = ['captain' => 'Captain', 'kagawad' => 'Kagawad', 'secretary' => 'Secretary', 'treasurer' => 'Treasurer', 'tanod' => 'Tanod', 'officer' => 'Officer'];
    $markerImageUrl = $marker?->image ? Storage::url($marker->image) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.pyrosense-favicon')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PyroSense - {{ $barangay->barangay_name }}</title>
    @vite(['resources/css/chief/dashboard.css', 'resources/css/app.css'])
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: Inter, system-ui, sans-serif; background: #fffdf8; color: #172033; }
        .main-content { margin-left: 280px; min-height: 100vh; padding: 20px 24px 40px; }
        .shell { max-width: 1240px; margin: 0 auto; }
        .topbar, .panel { background: rgba(255,255,255,.94); border: 1px solid #E9E5DE; border-radius: 20px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 18px 22px; margin-bottom: 18px; }
        .eyebrow { font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; color: #B45309; }
        .title { margin: 4px 0 0; font-size: clamp(1.6rem, 3vw, 2.4rem); line-height: 1; font-weight: 900; }
        .muted { color: #6B6E76; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 999px; padding: 10px 14px; font-weight: 800; font-size: 13px; border: 1px solid #E9E5DE; background: #fff; color: #374151; text-decoration: none; cursor: pointer; }
        .btn:hover { background: #FAF8F5; }
        .btn-primary { background: #E4602C; color: #fff; border-color: #E4602C; }
        .btn-danger { color: #B23A22; }
        .grid-main { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(320px, .95fr); gap: 18px; align-items: start; }
        .panel { overflow: hidden; }
        .panel-head { display: flex; justify-content: space-between; align-items: center; gap: 14px; padding: 16px 18px; background: #FAF8F5; border-bottom: 1px solid #E9E5DE; }
        .panel-body { padding: 18px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: grid; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        label { font-size: 12px; font-weight: 800; color: #4A4E58; }
        input, select, textarea { width: 100%; border: 1px solid #DDD6CC; border-radius: 12px; padding: 10px 12px; background: #fff; color: #172033; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: 2px solid rgba(228,96,44,.2); border-color: #E4602C; }
        .thumb { width: 76px; height: 76px; border-radius: 16px; object-fit: cover; border: 1px solid #E9E5DE; background: #FAF8F5; display: grid; place-items: center; color: #7A4FB8; }
        .image-preview { display: flex; align-items: center; gap: 12px; padding: 10px; border: 1px dashed #DDD6CC; border-radius: 16px; background: #FAF8F5; }
        .image-preview__copy { font-size: 12px; color: #6B6E76; font-weight: 700; }
        .marker-picker { min-height: 260px; border: 1px solid #E9E5DE; border-radius: 16px; overflow: hidden; background: #FAF8F5; }
        .map-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .map-toolbar small { color: #6B6E76; font-weight: 700; }
        .record-table { width: 100%; border-collapse: collapse; }
        .record-table th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #6B6E76; padding: 11px 12px; background: #FAF8F5; }
        .record-table td { padding: 13px 12px; border-top: 1px solid #E9E5DE; vertical-align: top; font-size: 13px; }
        .chip { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 5px 9px; font-size: 11px; font-weight: 800; }
        .chip-green { background: #E7F5EC; color: #1E7A4C; }
        .chip-amber { background: #FBF0DD; color: #A5680B; }
        .chip-blue { background: #EAF1FB; color: #3A6FB0; }
        .chip-gray { background: #F0EEEA; color: #4A4E58; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 0 0 18px; }
        .stat { border: 1px solid #E9E5DE; border-left: 3px solid #E4602C; border-radius: 16px; padding: 13px; background: #fff; }
        .stat strong { display: block; font-size: 24px; line-height: 1; }
        .officer-card { border: 1px solid #E9E5DE; border-radius: 16px; padding: 14px; background: #fff; margin-top: 12px; }
        .officer-head { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 12px; }
        .avatar { width: 38px; height: 38px; border-radius: 999px; background: #F3EEFB; color: #7A4FB8; display: grid; place-items: center; font-weight: 900; }
        .notice { padding: 12px 14px; border-radius: 14px; margin-bottom: 14px; font-size: 13px; font-weight: 700; }
        .notice-success { background: #E7F5EC; color: #1E7A4C; }
        .notice-error { background: #FBEAE4; color: #B23A22; }
        @media (max-width: 1040px) { .main-content { margin-left: 0; padding: 16px; } .grid-main { grid-template-columns: 1fr; } .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .topbar, .panel-head { align-items: flex-start; flex-direction: column; } .form-grid, .stat-grid { grid-template-columns: 1fr; } .record-table, .record-table tbody, .record-table tr, .record-table td { display: block; width: 100%; } .record-table thead { display: none; } }
    </style>
</head>
<body>
@include('chief.partials.sidebar', ['active' => 'barangays'])

<main class="main-content">
    <div class="shell">
        <section class="topbar">
            <div>
                <p class="eyebrow">Barangay Management</p>
                <h1 class="title">{{ $barangay->barangay_name }}</h1>
                <p class="muted">{{ $barangay->city?->city_name ?? 'No city linked' }}{{ $barangay->city?->province?->province_name ? ', '.$barangay->city->province->province_name : '' }}</p>
            </div>
            <a class="btn" href="{{ route('station-admin.barangays.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to cards</a>
        </section>

        @if (session('success'))
            <div class="notice notice-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif

        <div class="stat-grid">
            <div class="stat" style="border-left-color:#7A4FB8"><span class="muted">Personnel</span><strong>{{ $officers->count() }}</strong></div>
            <div class="stat" style="border-left-color:#B23A22"><span class="muted">Fire records</span><strong>{{ $fireRecords->count() + $historicalRecords->count() }}</strong></div>
            <div class="stat" style="border-left-color:#A5680B"><span class="muted">Hazard records</span><strong>{{ $hazardRecords->count() }}</strong></div>
            <div class="stat" style="border-left-color:#3A6FB0"><span class="muted">Mapped stations</span><strong>{{ $barangay->stations->count() }}</strong></div>
        </div>

        <div class="grid-main">
            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="text-lg font-extrabold">Barangay, hall, and marker info</h2>
                        <p class="muted text-sm">Edit the public barangay card, hall contact details, and map marker.</p>
                    </div>
                    @if ($markerImageUrl)
                        <img src="{{ $markerImageUrl }}" alt="{{ $barangay->barangay_name }} hall photo" class="thumb" data-image-preview-target>
                    @else
                        <div class="thumb" data-image-preview-target><i class="fa-solid fa-building text-2xl"></i></div>
                    @endif
                </div>
                <form method="POST" action="{{ route('station-admin.barangays.update', $barangay) }}" enctype="multipart/form-data" class="panel-body">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_to" value="show">
                    <div class="form-grid">
                        <div class="field">
                            <label for="barangay_name">Barangay name</label>
                            <input id="barangay_name" name="barangay_name" value="{{ old('barangay_name', $barangay->barangay_name) }}" required>
                        </div>
                        <div class="field">
                            <label>City</label>
                            <input value="{{ $barangay->city?->city_name ?? 'Assigned city' }}" disabled>
                        </div>
                        <div class="field">
                            <label for="hall_name">Barangay hall name</label>
                            <input id="hall_name" name="hall_name" value="{{ old('hall_name', $hall?->hall_name) }}" placeholder="{{ $barangay->barangay_name }} Barangay Hall">
                        </div>
                        <div class="field">
                            <label for="hall_contact_number">Hall contact number</label>
                            <input id="hall_contact_number" name="hall_contact_number" value="{{ old('hall_contact_number', $hall?->contact_number) }}" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="field">
                            <label for="hall_email">Hall email</label>
                            <input id="hall_email" type="email" name="hall_email" value="{{ old('hall_email', $hall?->email) }}" placeholder="barangay@example.com">
                        </div>
                        <div class="field">
                            <label for="barangay_card_image">Barangay hall/card image</label>
                            <input id="barangay_card_image" type="file" name="barangay_card_image" accept="image/*" data-image-preview-input>
                            <div class="image-preview" data-image-preview-panel>
                                @if ($markerImageUrl)
                                    <img src="{{ $markerImageUrl }}" alt="Current barangay hall image preview" class="thumb" data-image-preview-inline>
                                    <span class="image-preview__copy">Current photo on file. Choose a new image to preview before saving.</span>
                                @else
                                    <div class="thumb" data-image-preview-inline><i class="fa-solid fa-image text-xl"></i></div>
                                    <span class="image-preview__copy">No photo selected yet. Choose an image to preview it here.</span>
                                @endif
                            </div>
                        </div>
                        <div class="field">
                            <label for="marker_title">Marker title</label>
                            <input id="marker_title" name="marker_title" value="{{ old('marker_title', $marker?->title) }}" placeholder="Barangay hall marker">
                        </div>
                        <div class="field">
                            <label for="marker_status">Marker status</label>
                            <select id="marker_status" name="marker_status">
                                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'completed' => 'Completed'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('marker_status', $marker?->status ?? 'active') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="marker_lat">Latitude</label>
                            <input id="marker_lat" name="marker_lat" value="{{ old('marker_lat', $marker?->lat) }}" placeholder="15.9750000">
                        </div>
                        <div class="field">
                            <label for="marker_lng">Longitude</label>
                            <input id="marker_lng" name="marker_lng" value="{{ old('marker_lng', $marker?->lng) }}" placeholder="120.5700000">
                        </div>
                        <div class="field full">
                            <div class="map-toolbar">
                                <label for="barangayMarkerPicker">Marker coordinate picker</label>
                                <div class="flex flex-wrap items-center gap-2">
                                    <small>Click the map or drag the marker to adjust the barangay hall location.</small>
                                    <button type="button" class="btn" data-use-browser-location><i class="fa-solid fa-location-crosshairs"></i> Use my location</button>
                                </div>
                            </div>
                            <div
                                id="barangayMarkerPicker"
                                class="marker-picker"
                                data-default-lat="{{ old('marker_lat', $marker?->lat) ?: 15.975 }}"
                                data-default-lng="{{ old('marker_lng', $marker?->lng) ?: 120.57 }}"
                            ></div>
                        </div>
                        <div class="field full">
                            <label for="marker_description">Marker description</label>
                            <textarea id="marker_description" name="marker_description" rows="3" placeholder="Short map marker description">{{ old('marker_description', $marker?->description) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap justify-end gap-2">
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save barangay info</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <div>
                        <h2 class="text-lg font-extrabold">Personnel</h2>
                        <p class="muted text-sm">Add, edit, or remove people assigned to this barangay.</p>
                    </div>
                    <span class="chip chip-gray">{{ $officers->count() }} linked</span>
                </div>
                <div class="panel-body">
                    <form data-officer-form action="{{ route('station-admin.barangay-officers.store') }}" method="POST" class="officer-card" data-reset-on-success>
                        <input type="hidden" name="mode" value="new">
                        <input type="hidden" name="barangay_id" value="{{ $barangay->barangay_id }}">
                        <div class="form-grid">
                            <div class="field"><label>First name</label><input name="first_name" required></div>
                            <div class="field"><label>Last name</label><input name="last_name" required></div>
                            <div class="field"><label>Middle name</label><input name="middle_name"></div>
                            <div class="field"><label>Contact number</label><input name="contact_number" required placeholder="09XXXXXXXXX"></div>
                            <div class="field"><label>Email</label><input type="email" name="email" required></div>
                            <div class="field"><label>Street/address</label><input name="street" placeholder="Barangay Hall"></div>
                            <div class="field">
                                <label>Position</label>
                                <select name="position" required>
                                    @foreach ($positions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field">
                                <label>Status</label>
                                <select name="status" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus"></i> Add personnel</button>
                        </div>
                    </form>

                    @forelse ($officers as $officer)
                        @php
                            $user = $officer->user;
                            $name = trim(collect([$user?->first_name, $user?->middle_name, $user?->last_name])->filter()->implode(' ')) ?: 'Unlinked officer';
                            $initials = collect(explode(' ', $name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
                            $positionKey = strtolower((string) $officer->position);
                        @endphp
                        <form data-officer-form action="{{ route('station-admin.barangay-officers.update', $officer) }}" method="POST" class="officer-card">
                            <input type="hidden" name="_method" value="PUT">
                            <div class="officer-head">
                                <div class="flex items-center gap-3">
                                    <div class="avatar">{{ $initials }}</div>
                                    <div>
                                        <p class="font-extrabold">{{ $name }}</p>
                                        <p class="muted text-xs">{{ $user?->email ?? 'No account email' }}</p>
                                    </div>
                                </div>
                                <span class="chip {{ $officer->status === 'active' ? 'chip-green' : 'chip-gray' }}">{{ ucfirst($officer->status ?? 'inactive') }}</span>
                            </div>
                            <div class="form-grid">
                                <div class="field"><label>First name</label><input name="first_name" value="{{ $user?->first_name }}" required></div>
                                <div class="field"><label>Last name</label><input name="last_name" value="{{ $user?->last_name }}" required></div>
                                <div class="field"><label>Middle name</label><input name="middle_name" value="{{ $user?->middle_name }}"></div>
                                <div class="field"><label>Contact number</label><input name="contact_number" value="{{ $user?->contact_number }}" required></div>
                                <div class="field"><label>Email</label><input type="email" name="email" value="{{ $user?->email }}" required></div>
                                <div class="field"><label>Street/address</label><input name="street" value="{{ $user?->address?->street }}"></div>
                                <div class="field">
                                    <label>Position</label>
                                    <select name="position" required>
                                        @foreach ($positions as $value => $label)
                                            <option value="{{ $value }}" @selected($positionKey === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Status</label>
                                    <select name="status" required>
                                        <option value="active" @selected($officer->status === 'active')>Active</option>
                                        <option value="inactive" @selected($officer->status === 'inactive')>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap justify-end gap-2">
                                <button class="btn btn-danger" type="button" data-officer-delete="{{ route('station-admin.barangay-officers.destroy', $officer) }}"><i class="fa-solid fa-trash"></i> Remove</button>
                                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save personnel</button>
                            </div>
                        </form>
                    @empty
                        <p class="muted mt-4">No personnel assigned yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="panel mt-5">
            <div class="panel-head">
                <div>
                    <h2 class="text-lg font-extrabold">Fire records</h2>
                    <p class="muted text-sm">Records connected to {{ $barangay->barangay_name }}.</p>
                </div>
                <span class="chip chip-amber">{{ $fireRecords->count() + $historicalRecords->count() }} records</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="record-table">
                    <thead><tr><th>Date</th><th>Type</th><th>Cause</th><th>Count</th><th>Notes / location</th><th>Actions</th></tr></thead>
                    <tbody>
                    @if ($fireRecords->isEmpty() && $historicalRecords->isEmpty())
                        <tr><td colspan="6" class="muted">No barangay fire records saved yet.</td></tr>
                    @endif
                    @foreach ($fireRecords as $record)
                        <tr>
                            <td>{{ optional($record->incident_date)->format('M d, Y') ?? 'Unknown' }}</td>
                            <td><span class="chip chip-amber">{{ ucfirst($record->fire_classification ?? 'Fire record') }}</span></td>
                            <td>{{ $record->fire_cause ?: 'Not recorded' }}</td>
                            <td>{{ $record->incident_count ?? 1 }}</td>
                            <td class="muted">{{ $record->notes ?: ($record->incident?->description ?? 'No notes recorded') }}</td>
                            <td>
                                @if ($record->incident_id)
                                    <a class="btn" href="{{ route('station-admin.management', ['tab' => 'incidents', 'incident_id' => $record->incident_id]) }}"><i class="fa-solid fa-pen"></i> Edit incident</a>
                                @else
                                    <a class="btn" href="{{ route('station-admin.management', ['tab' => 'historical-fire-records', 'barangay_id' => $barangay->barangay_id]) }}"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open records</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @foreach ($historicalRecords as $record)
                        <tr>
                            <td>{{ optional($record->incident_date)->format('M d, Y') ?? 'Unknown' }}</td>
                            <td><span class="chip chip-gray">Historical</span></td>
                            <td>{{ $record->fire_cause ?: 'Not recorded' }}</td>
                            <td>{{ $record->incident_count ?? 1 }}</td>
                            <td class="muted">{{ $record->incident_location ?: ($record->notes ?: 'No location notes recorded') }}</td>
                            <td>
                                <a class="btn" href="{{ route('station-admin.management', ['tab' => 'historical-fire-records', 'historical_id' => $record->historical_fire_incident_id]) }}"><i class="fa-solid fa-pen"></i> Edit record</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel mt-5">
            <div class="panel-head">
                <div>
                    <h2 class="text-lg font-extrabold">Hazard records</h2>
                    <p class="muted text-sm">Risk and hazard areas linked through the barangay hall.</p>
                </div>
                <span class="chip chip-blue">{{ $hazardRecords->count() }} records</span>
            </div>
            <div class="panel-body overflow-x-auto">
                <table class="record-table">
                    <thead><tr><th>Marker</th><th>Entry</th><th>Assessment</th><th>Status</th><th>Station</th><th>Updated</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse ($hazardRecords as $risk)
                        <tr>
                            <td>
                                <p class="font-extrabold">{{ $risk->marker?->title ?? $risk->street_or_sitio ?? 'Mapped hazard' }}</p>
                                <p class="muted text-xs">{{ $risk->marker?->description ?? $risk->remarks }}</p>
                            </td>
                            <td>{{ ucfirst($risk->entry_type ?? $risk->hazard_category ?? 'Risk') }}</td>
                            <td><span class="chip chip-amber">{{ $risk->assessment_level ?? $risk->condition_label ?? 'Not assessed' }}</span></td>
                            <td>{{ $risk->status ?? $risk->review_status ?? 'Unknown' }}</td>
                            <td>{{ $risk->station?->station_name ?? 'Not assigned' }}</td>
                            <td>{{ optional($risk->updated_at)->format('M d, Y') ?? 'Unknown' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    <a class="btn" href="{{ route('station-admin.risk-reviews.index', ['risk_area_id' => $risk->risk_area_id]) }}"><i class="fa-solid fa-clipboard-check"></i> Review</a>
                                    @if ($risk->marker?->lat && $risk->marker?->lng)
                                        <a class="btn" href="{{ route('station-admin.maps', ['focus_marker_id' => $risk->marker_id, 'focus_lat' => $risk->marker->lat, 'focus_lng' => $risk->marker->lng]) }}"><i class="fa-solid fa-map-location-dot"></i> Map</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">No hazard records linked to this barangay yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>

<script>
(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const latInput = document.getElementById('marker_lat');
    const lngInput = document.getElementById('marker_lng');
    const pickerElement = document.getElementById('barangayMarkerPicker');

    const setImagePreview = (url) => {
        document.querySelectorAll('[data-image-preview-target], [data-image-preview-inline]').forEach((target) => {
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Selected barangay hall image preview';
            image.className = 'thumb';
            if (target.hasAttribute('data-image-preview-target')) {
                image.setAttribute('data-image-preview-target', '');
            }
            if (target.hasAttribute('data-image-preview-inline')) {
                image.setAttribute('data-image-preview-inline', '');
            }
            target.replaceWith(image);
        });

        const copy = document.querySelector('[data-image-preview-panel] .image-preview__copy');
        if (copy) copy.textContent = 'Preview ready. Save barangay info to upload this image.';
    };

    document.querySelector('[data-image-preview-input]')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        if (!file || !file.type.startsWith('image/')) return;
        setImagePreview(URL.createObjectURL(file));
    });

    if (pickerElement && latInput && lngInput && window.L) {
        const fallbackLat = Number(pickerElement.dataset.defaultLat || 15.975);
        const fallbackLng = Number(pickerElement.dataset.defaultLng || 120.57);
        const initialLat = Number(latInput.value || fallbackLat);
        const initialLng = Number(lngInput.value || fallbackLng);
        const map = L.map(pickerElement, { scrollWheelZoom: true }).setView([initialLat, initialLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
        const writeCoordinates = (latLng) => {
            latInput.value = Number(latLng.lat).toFixed(7);
            lngInput.value = Number(latLng.lng).toFixed(7);
            marker.setLatLng(latLng);
        };

        map.on('click', (event) => writeCoordinates(event.latlng));
        marker.on('dragend', () => writeCoordinates(marker.getLatLng()));

        const syncFromInputs = () => {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            const latLng = L.latLng(lat, lng);
            marker.setLatLng(latLng);
            map.panTo(latLng);
        };

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);

        document.querySelector('[data-use-browser-location]')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Your browser does not support location access.');
                return;
            }

            navigator.geolocation.getCurrentPosition((position) => {
                const latLng = L.latLng(position.coords.latitude, position.coords.longitude);
                writeCoordinates(latLng);
                map.setView(latLng, 17);
            }, () => {
                alert('Unable to get your current location. You can still click the map to set the marker.');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        window.setTimeout(() => map.invalidateSize(), 200);
    }

    const getErrorMessage = async (response) => {
        try {
            const payload = await response.json();
            if (payload?.message) return payload.message;
            const first = Object.values(payload?.errors || {})?.[0]?.[0];
            return first || 'The request could not be completed.';
        } catch (error) {
            return 'The request could not be completed.';
        }
    };

    document.querySelectorAll('[data-officer-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            button && (button.disabled = true);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: new FormData(form),
                });

                if (!response.ok) throw new Error(await getErrorMessage(response));
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Unable to save personnel.');
            } finally {
                button && (button.disabled = false);
            }
        });
    });

    document.querySelectorAll('[data-officer-delete]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!confirm('Remove this personnel assignment from the barangay?')) return;
            button.disabled = true;
            try {
                const response = await fetch(button.dataset.officerDelete, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                });

                if (!response.ok) throw new Error(await getErrorMessage(response));
                window.location.reload();
            } catch (error) {
                alert(error.message || 'Unable to remove personnel.');
                button.disabled = false;
            }
        });
    });
})();
</script>
</body>
</html>
