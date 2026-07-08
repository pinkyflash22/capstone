<section class="fire-report-form__section">
    <div class="fire-report-form__section-head">
        <div>
            <div class="fire-report-form__eyebrow"><i class="fa-solid fa-fire-flame-curved"></i> Basic Details</div>
            <p class="fire-report-form__hint">Start with the main fire record information.</p>
        </div>
    </div>

    <div class="fire-report-form__grid">
        <div class="fire-report-form__field fire-report-form__field--full">
            <label for="{{ $prefix }}_title">Incident Title <span class="fire-report-required">Required</span></label>
            <input id="{{ $prefix }}_title" name="title" type="text" value="{{ old('title', $record['title'] ?? '') }}" placeholder="Example: Residential fire near covered court">
        </div>

        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_incident_date">Incident Date <span class="fire-report-required">Required</span></label>
            <input id="{{ $prefix }}_incident_date" name="incident_date" type="date" value="{{ old('incident_date', $record['incident_date'] ?? now()->toDateString()) }}">
        </div>

        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_incident_count">Affected Count</label>
            <input id="{{ $prefix }}_incident_count" name="incident_count" type="number" min="1" max="100" value="{{ old('incident_count', $record['incident_count'] ?? 1) }}">
        </div>

        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_fire_classification">Classification</label>
            <select id="{{ $prefix }}_fire_classification" name="fire_classification">
                <option value="accidental" @selected(old('fire_classification', $record['fire_classification'] ?? '') === 'accidental')>Accidental</option>
                <option value="intentional" @selected(old('fire_classification', $record['fire_classification'] ?? '') === 'intentional')>Intentional</option>
                <option value="undetermined" @selected(old('fire_classification', $record['fire_classification'] ?? 'undetermined') === 'undetermined')>Undetermined</option>
            </select>
        </div>

        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_fire_cause">Suspected Cause</label>
            <input id="{{ $prefix }}_fire_cause" name="fire_cause" type="text" value="{{ old('fire_cause', $record['fire_cause'] ?? '') }}" placeholder="Electrical wiring, open flame, LPG">
        </div>
    </div>
</section>

<section class="fire-report-form__section">
    <div class="fire-report-form__section-head">
        <div>
            <div class="fire-report-form__eyebrow"><i class="fa-solid fa-location-dot"></i> Location Details</div>
            <p class="fire-report-form__hint">Add the exact area and quick access notes.</p>
        </div>
    </div>

    <div class="fire-report-form__grid">
        <div class="fire-report-form__field fire-report-form__field--full">
            <label for="{{ $prefix }}_location_description">Location Description <span class="fire-report-required">Required</span></label>
            <textarea id="{{ $prefix }}_location_description" name="location_description" placeholder="Street, purok, landmark, access notes">{{ old('location_description', $record['location_description'] ?? '') }}</textarea>
        </div>

        <div class="fire-report-form__field fire-report-form__field--full">
            <label for="{{ $prefix }}_notes">Field Notes</label>
            <textarea id="{{ $prefix }}_notes" name="notes" placeholder="Visible spread, witness notes, urgent needs">{{ old('notes', $record['notes'] ?? '') }}</textarea>
        </div>
    </div>
</section>

<section class="fire-report-form__section fire-report-location-panel">
    <div class="fire-report-location-panel__header">
        <div>
            <div class="fire-report-form__eyebrow"><i class="fa-solid fa-location-crosshairs"></i> Coordinates</div>
            <p class="fire-report-form__hint">Use live GPS for faster entry or adjust the point manually.</p>
        </div>
        <button type="button" class="fire-report-location-btn" id="{{ $prefix }}_useCurrentLocationBtn">
            <i class="fa-solid fa-location-crosshairs"></i>
            Use Current Location
        </button>
    </div>

    <div class="fire-report-form__grid">
        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_lat">Latitude</label>
            <input id="{{ $prefix }}_lat" name="lat" type="text" value="{{ old('lat', $record['lat'] ?? '') }}" placeholder="15.8222000">
        </div>
        <div class="fire-report-form__field">
            <label for="{{ $prefix }}_lng">Longitude</label>
            <input id="{{ $prefix }}_lng" name="lng" type="text" value="{{ old('lng', $record['lng'] ?? '') }}" placeholder="120.3297000">
        </div>
    </div>
</section>
