<article id="barangayRiskSubmissionModal" class="barangay-risk-modal hidden" aria-hidden="true">
    <div class="barangay-risk-modal__backdrop" data-close-risk-modal></div>
    <div class="barangay-risk-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="barangayRiskModalTitle">
        <header class="barangay-risk-modal__header">
            <div>
                <div class="panel-eyebrow">Submit</div>
                <h3 class="panel-title" id="barangayRiskModalTitle">Add risk marker</h3>
            </div>
            <button type="button" class="barangay-risk-modal__close" data-close-risk-modal aria-label="Close risk submission"><i class="fa-solid fa-xmark"></i></button>
        </header>
        <div class="barangay-risk-modal__body">
            <div class="barangay-risk-wizard__progress" aria-label="Submission progress">
                <button type="button" class="barangay-risk-wizard__progress-item is-active" data-risk-step-target="0" data-step="1">1. Basics</button>
                <button type="button" class="barangay-risk-wizard__progress-item" data-risk-step-target="1" data-step="2">2. Profile</button>
                <button type="button" class="barangay-risk-wizard__progress-item" data-risk-step-target="2" data-step="3">3. Evidence</button>
                <button type="button" class="barangay-risk-wizard__progress-item" data-risk-step-target="3" data-step="4">4. Location</button>
            </div>

            <form method="POST" action="{{ route('barangay.risk-map.store') }}" class="barangay-risk-form mt-6" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="entry_type" value="risk">
                <details class="barangay-risk-step is-active" open>
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
                                <input id="risk_area_title" name="title" type="text" value="{{ old('title') }}" class="barangay-risk-input" placeholder="Example: Dense light-material housing near creek" required>
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-signature text-orange-500 mr-1"></i>Use a short area name officers will recognize quickly.</p>
                            </div>
                            <div class="barangay-risk-field barangay-risk-field--full">
                                <label for="risk_area_location_description">Location Description <span class="barangay-risk-field__required">Required</span></label>
                                <textarea id="risk_area_location_description" name="location_description" rows="3" class="barangay-risk-textarea" placeholder="Purok, street, landmark, access constraints" required>{{ old('location_description') }}</textarea>
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-location-dot text-orange-500 mr-1"></i>Say what responders should look for when approaching the area.</p>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_street">Street / Purok / Sitio</label>
                                <input id="risk_area_street" name="street_or_sitio" type="text" value="{{ old('street_or_sitio') }}" class="barangay-risk-input" placeholder="Specific local reference">
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_station_id">Linked Station</label>
                                <input type="hidden" id="risk_area_station_id" name="station_id" value="{{ old('station_id', $autoLinkedStationId ?? '') }}">
                                <input type="text" class="barangay-risk-input" value="{{ $autoLinkedStation?->station_name ?? 'Will be matched from barangay/municipality' }}" readonly>
                                <p class="barangay-risk-field__hint"><i class="fa-solid fa-building-shield text-orange-500 mr-1"></i>Station is assigned automatically from barangay coverage or municipality match.</p>
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
                                <select id="risk_area_type" name="risk_type" class="barangay-risk-select" required>
                                    <option value="">Select risk type</option>
                                    @foreach ($riskTypeGroups as $group => $riskTypes)
                                        <optgroup label="{{ $group }}">
                                            @foreach ($riskTypes as $riskTypeValue)
                                                <option value="{{ $riskTypeValue }}" @selected(old('risk_type') === $riskTypeValue)>{{ $riskTypeOptions[$riskTypeValue] ?? ucwords(str_replace('_', ' ', $riskTypeValue)) }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="barangay-risk-field">
                                <label for="risk_area_condition">Risk Condition</label>
                                <select id="risk_area_condition" name="condition_code" class="barangay-risk-select" required>
                                    <option value="">Select a risk type first</option>
                                    @foreach ($riskConditionOptions as $riskTypeValue => $conditions)
                                        @foreach ($conditions as $conditionValue => $conditionLabel)
                                            <option value="{{ $conditionValue }}" data-risk-type="{{ $riskTypeValue }}" @selected(old('condition_code') === $conditionValue)>{{ $conditionLabel }}</option>
                                        @endforeach
                                    @endforeach
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
                            <label class="barangay-risk-chip"><input type="checkbox" name="near_hydrant" value="1" @checked(old('near_hydrant'))> Near hydrant</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="near_water_source" value="1" @checked(old('near_water_source'))> Near water source</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="blocked_hydrant" value="1" @checked(old('blocked_hydrant'))> Blocked hydrant</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="informal_settlement" value="1" @checked(old('informal_settlement'))> Informal settlement</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="cooking_fire_prone_activity" value="1" @checked(old('cooking_fire_prone_activity'))> Cooking / fire-prone activity</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="combustible_materials_present" value="1" @checked(old('combustible_materials_present'))> Combustible materials present</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="poor_waste_disposal" value="1" @checked(old('poor_waste_disposal'))> Poor waste disposal</label>
                            <label class="barangay-risk-chip"><input type="checkbox" name="previous_fire_prone_location" value="1" @checked(old('previous_fire_prone_location'))> Previous fire-prone location</label>
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
                                    <input id="risk_area_lat" name="lat" type="text" value="{{ old('lat') }}" class="barangay-risk-input" readonly required>
                                </div>
                                <div class="barangay-risk-field">
                                    <label for="risk_area_lng">Longitude</label>
                                    <input id="risk_area_lng" name="lng" type="text" value="{{ old('lng') }}" class="barangay-risk-input" readonly required>
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
                <div class="barangay-risk-wizard__actions">
                    <button type="button" class="barangay-risk-wizard__button barangay-risk-wizard__button--back" data-risk-step-back disabled>
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
                    </button>
                    <span class="text-xs font-bold text-slate-500" data-risk-step-label>Step 1 of 4</span>
                    <button type="button" class="barangay-risk-wizard__button barangay-risk-wizard__button--next" data-risk-step-next>
                        Next <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</article>
