<?php

namespace App\Http\Controllers;

use App\Models\Marker;
use App\Models\Multimedia;
use App\Models\RiskArea;
use App\Models\RiskAreaPhoto;
use App\Models\StationOfficer;
use App\Models\Status;
use App\Services\BoundaryMatcherService;
use App\Services\OperationalMapScope;
use App\Support\MarkerFeedBuilder;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MarkerController extends Controller
{
    private const CITIZEN_NEARBY_INCIDENT_RADIUS_METERS = 3000;

    private const MARKER_CATEGORIES = ['fire', 'station', 'hydrant', 'school', 'barangay', 'bridge', 'risk', 'hazard'];

    private const MARKER_STATUSES = ['active', 'inactive', 'completed'];

    private function mapCategoryNameToEnum(?string $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return match (true) {
            $normalized === 'fire',
            $normalized === 'incident',
            str_contains($normalized, 'incident'),
            $normalized === 'fire incident' => 'fire',
            $normalized === 'station',
            str_contains($normalized, 'station') => 'station',
            $normalized === 'hydrant',
            str_contains($normalized, 'hydrant') => 'hydrant',
            $normalized === 'school',
            str_contains($normalized, 'school') => 'school',
            $normalized === 'barangay',
            str_contains($normalized, 'barangay') => 'barangay',
            $normalized === 'bridge',
            str_contains($normalized, 'bridge') => 'bridge',
            $normalized === 'risk',
            str_contains($normalized, 'risk') => 'risk',
            $normalized === 'hazard',
            str_contains($normalized, 'hazard') => 'hazard',
            default => in_array($normalized, self::MARKER_CATEGORIES, true) ? $normalized : null,
        };
    }

    private function resolveCategory(?string $category, mixed $legacyCategoryId = null): ?string
    {
        $resolved = $this->mapCategoryNameToEnum($category);
        if ($resolved) {
            return $resolved;
        }

        if ($legacyCategoryId) {
            return match ((int) $legacyCategoryId) {
                1 => 'station',
                2, 3 => 'fire',
                4 => 'hydrant',
                5 => 'school',
                6 => 'barangay',
                7 => 'bridge',
                default => null,
            };
        }

        return null;
    }

    private function resolveStatus(?string $status, mixed $legacyStatusId = null): string
    {
        $normalized = strtolower(trim((string) $status));
        if (in_array($normalized, self::MARKER_STATUSES, true)) {
            return $normalized;
        }

        if ($legacyStatusId) {
            $legacyStatus = Status::find($legacyStatusId);
            $legacyName = strtolower(trim((string) ($legacyStatus?->status_name ?? '')));
            if (in_array($legacyName, self::MARKER_STATUSES, true)) {
                return $legacyName;
            }
        }

        return 'active';
    }

    private function publicValue(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized === '' ? null : $normalized;
    }

    private function formatFullAddress($address): ?string
    {
        if (! $address) {
            return null;
        }

        $parts = array_filter([
            $this->publicValue($address->street ?? null),
            $this->publicValue($address->barangay?->barangay_name ?? null),
            $this->publicValue($address->barangay?->city?->city_name ?? null),
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    private function publicDisplayName($user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(implode(' ', array_filter([
            $this->publicValue($user->first_name ?? null),
            $this->publicValue($user->last_name ?? null),
        ])));

        return $name !== '' ? $name : $this->publicValue($user->name ?? null);
    }

    private function resolveStationChiefName($station): ?string
    {
        if (! $station?->stationOfficers) {
            return null;
        }

        $chiefOfficer = $station->stationOfficers
            ->filter(function ($officer) {
                $role = strtolower((string) ($officer->user?->role?->role ?? ''));
                $rank = strtolower((string) ($officer->rank ?? ''));
                $status = strtolower((string) ($officer->status ?? ''));

                $isChief = $role === 'chief' || str_contains($rank, 'chief');
                $isActive = $status === '' || in_array($status, ['active', 'available', 'on duty', 'on-duty'], true);

                return $isChief && $isActive;
            })
            ->sortByDesc(function ($officer) {
                return strtolower((string) ($officer->user?->role?->role ?? '')) === 'chief' ? 1 : 0;
            })
            ->first();

        return $this->publicDisplayName($chiefOfficer?->user);
    }

    private function spamMarkerQuery(Request $request, OperationalMapScope $operationalScope)
    {
        $viewer = $request->user();
        $query = Marker::query()
            ->with([
                'stations',
                'hydrants.station',
                'incidents.team',
                'riskAreas.barangayHall.barangay',
            ])
            ->where('status', 'inactive');

        if ($operationalScope->hasProvinceWideAccess($viewer)) {
            return $query;
        }

        $stationIds = $operationalScope->stationIds($viewer);
        $coveredBarangayIds = $operationalScope->coveredBarangayIds($viewer);

        if ($stationIds->isEmpty() && $coveredBarangayIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($scope) use ($stationIds, $coveredBarangayIds) {
            if ($stationIds->isNotEmpty()) {
                $scope->orWhereHas('stations', fn ($stations) => $stations->whereIn('station_id', $stationIds->all()))
                    ->orWhereHas('hydrants', fn ($hydrants) => $hydrants->whereIn('station_id', $stationIds->all()))
                    ->orWhereHas('incidents', function ($incidents) use ($stationIds) {
                        $incidents->whereIn('station_id', $stationIds->all())
                            ->orWhereHas('team', fn ($teams) => $teams->whereIn('station_id', $stationIds->all()));
                    })
                    ->orWhereHas('riskAreas', fn ($riskAreas) => $riskAreas->whereIn('station_id', $stationIds->all()));
            }

            if ($coveredBarangayIds->isNotEmpty()) {
                $scope->orWhereHas('riskAreas.barangayHall', fn ($halls) => $halls->whereIn('barangay_id', $coveredBarangayIds->all()));
            }
        });
    }

    private function markerSpamAuditUpdates(?int $userId): array
    {
        $updates = ['status' => 'inactive'];

        if (Schema::hasColumn('markers', 'spammed_by')) {
            $updates['spammed_by'] = $userId;
        }

        if (Schema::hasColumn('markers', 'spammed_at')) {
            $updates['spammed_at'] = now();
        }

        return $updates;
    }

    private function markerSpamRestoreUpdates(): array
    {
        $updates = ['status' => 'active'];

        if (Schema::hasColumn('markers', 'spammed_by')) {
            $updates['spammed_by'] = null;
        }

        if (Schema::hasColumn('markers', 'spammed_at')) {
            $updates['spammed_at'] = null;
        }

        return $updates;
    }

    public function spamIndex(Request $request, OperationalMapScope $operationalScope)
    {
        $markers = $this->spamMarkerQuery($request, $operationalScope)
            ->orderByDesc('updated_at')
            ->orderByDesc('marker_id')
            ->paginate(15)
            ->withQueryString();

        $role = $operationalScope->role($request->user());
        $isProvincial = $operationalScope->hasProvinceWideAccess($request->user());

        return view('markers.spam-review', [
            'markers' => $markers,
            'isProvincial' => $isProvincial,
            'roleName' => $role,
        ]);
    }

    public function restoreSpam(Request $request, int $markerId, OperationalMapScope $operationalScope)
    {
        $marker = $this->spamMarkerQuery($request, $operationalScope)
            ->whereKey($markerId)
            ->firstOrFail();

        $marker->update($this->markerSpamRestoreUpdates());

        Cache::forget('map:markers:index:v2');
        Cache::forget('map:markers:index:v3_public');
        UserActivityLogger::log('restored spam marker', Auth::id(), $request);

        return back()->with('success', 'Marker restored to the map.');
    }

    public function index(Request $request)
    {
        $viewer = Auth::user();
        $markers = MarkerFeedBuilder::buildForViewer($viewer);
        $role = strtolower(trim((string) ($viewer?->role?->role ?? '')));

        if ($role === 'citizen') {
            $validated = $request->validate([
                'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
                'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
            ]);
            $latitude = isset($validated['lat']) ? (float) $validated['lat'] : null;
            $longitude = isset($validated['lng']) ? (float) $validated['lng'] : null;
            $ownIncidentMarkerIds = \App\Models\Incident::query()
                ->where('reported_by', $viewer->user_id)
                ->whereNotNull('marker_id')
                ->pluck('marker_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $markers = $markers->filter(function (array $marker) use ($latitude, $longitude, $ownIncidentMarkerIds) {
                $category = strtolower(trim((string) ($marker['category'] ?? '')));
                $isIncidentMarker = $category === 'fire' || ! empty($marker['incident_public']);
                if (! $isIncidentMarker) {
                    return true;
                }

                if (in_array((int) ($marker['marker_id'] ?? 0), $ownIncidentMarkerIds, true)) {
                    return true;
                }

                if ($latitude === null || $longitude === null) {
                    return false;
                }

                $status = strtolower(trim((string) ($marker['incident_public']['status'] ?? '')));
                if (! in_array($status, ['reported', 'responding', 'en-route', 'en route', 'on scene', 'on-scene'], true)) {
                    return false;
                }

                $markerLat = filter_var($marker['lat'] ?? null, FILTER_VALIDATE_FLOAT);
                $markerLng = filter_var($marker['lng'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($markerLat === false || $markerLng === false) {
                    return false;
                }

                return $this->calculateDistanceMeters(
                    $latitude,
                    $longitude,
                    (float) $markerLat,
                    (float) $markerLng
                ) <= self::CITIZEN_NEARBY_INCIDENT_RADIUS_METERS;
            })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $markers->values()->all(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function store(Request $request, BoundaryMatcherService $boundaryMatcherService)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],
            'category' => ['nullable', Rule::in(self::MARKER_CATEGORIES)],
            'status_id' => 'nullable',
            'markerCategory_id' => 'nullable',
            'classification' => ['nullable', Rule::in(['people_density', 'housing_material', 'housing_spacing', 'electrical', 'wiring', 'flammable'])],
            'assessment_level' => ['nullable', Rule::in(['low', 'moderate', 'high'])],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $resolvedStatus = $this->resolveStatus(
            $validated['status'] ?? null,
            $request->input('status_id')
        );

        $resolvedCategory = $this->resolveCategory(
            $validated['category'] ?? null,
            $request->input('markerCategory_id')
        );

        if (in_array($resolvedCategory, ['risk', 'hazard'], true)) {
            return $this->storeRiskOrHazardMarker($request, $validated, $resolvedCategory, $resolvedStatus, $boundaryMatcherService);
        }

        $data = Marker::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'status' => $resolvedStatus,
            'category' => $resolvedCategory,
        ]);

        $data->refresh();
        $data->load([
            'stations.address.barangay.city',
            'stations.stationOfficers.user.role',
            'barangayHall.address.barangay.city',
            'barangayHall.barangay.city',
            'hydrants.station',
            'incidents',
            'riskAreas',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marker saved successfully.',
            'data' => $data,
            'marker' => $data,
        ], 201);
    }

    private function storeRiskOrHazardMarker(
        Request $request,
        array $validated,
        string $category,
        string $status,
        BoundaryMatcherService $boundaryMatcherService
    ) {
        $request->validate([
            'classification' => ['required', Rule::in(['people_density', 'housing_material', 'housing_spacing', 'electrical', 'wiring', 'flammable'])],
            'assessment_level' => ['required', Rule::in(['low', 'moderate', 'high'])],
        ]);

        $lat = round((float) $validated['lat'], 7);
        $lng = round((float) $validated['lng'], 7);
        $hall = $boundaryMatcherService->locateBarangayHall($lat, $lng);

        if (! $hall) {
            throw ValidationException::withMessages([
                'lat' => 'Place the risk or hazard marker inside a saved barangay boundary with a linked barangay hall.',
            ]);
        }

        $classification = (string) $validated['classification'];
        $assessmentLevel = (string) $validated['assessment_level'];
        $profile = $this->riskHazardProfile($category, $classification);
        $stationId = DB::table('station_barangay_coverage')
            ->where('barangay_id', $hall->barangay_id)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN LOWER(COALESCE(coverage_type, '')) = 'primary' THEN 0 ELSE 1 END")
            ->value('station_id');

        if (! $stationId) {
            $stationId = StationOfficer::query()->where('user_id', Auth::id())->value('station_id');
        }

        $riskArea = DB::transaction(function () use ($request, $validated, $category, $status, $lat, $lng, $hall, $stationId, $assessmentLevel, $profile) {
            $marker = Marker::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'lat' => $lat,
                'lng' => $lng,
                'status' => $status,
                'category' => $category,
            ]);

            $riskArea = RiskArea::create([
                'marker_id' => $marker->marker_id,
                'barangay_hall_id' => $hall->barangay_hall_id,
                'station_id' => $stationId,
                'entry_type' => $category,
                'hazard_category' => $profile['hazard_category'],
                'risk_type' => $profile['risk_type'],
                'risk_factor' => $profile['risk_factor'],
                'mode' => $profile['mode'],
                'condition_code' => $profile['condition_code'],
                'condition_label' => $profile['condition_label'],
                'hazard_type' => $profile['hazard_type'],
                'risk_score' => match ($assessmentLevel) {
                    'high' => 80,
                    'moderate' => 55,
                    default => 25,
                },
                'assessment_level' => $assessmentLevel,
                'assessment_description' => $validated['description'] ?? null,
                'status' => 'verified',
                'review_status' => 'approved',
                'review_type' => 'create',
                'review_notes' => 'Verified map marker created by an authorized administrator.',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'reported_by' => Auth::id(),
            ]);

            $firstPhotoPath = null;
            foreach ($request->file('photos', []) as $photo) {
                $storedPath = MediaPath::storeForDisk($photo->store('risk-areas', 'supabase'), 'supabase');
                $firstPhotoPath ??= $storedPath;
                RiskAreaPhoto::create([
                    'risk_area_id' => $riskArea->risk_area_id,
                    'photo_path' => $storedPath,
                    'photo_name' => $photo->getClientOriginalName(),
                ]);
            }

            if ($firstPhotoPath) {
                $marker->update(['image' => $firstPhotoPath]);
            }

            return $riskArea;
        });

        Cache::forget('map:markers:index:v2');
        Cache::forget('map:markers:index:v3_public');
        RiskAreaReviewController::flushRiskReviewCache();

        $marker = $riskArea->marker()->with('riskAreas.photos')->first();

        return response()->json([
            'success' => true,
            'message' => ucfirst($category).' marker saved and matched to its barangay.',
            'data' => $marker,
            'marker' => $marker,
        ], 201);
    }

    private function riskHazardProfile(string $category, string $classification): array
    {
        $profiles = [
            'people_density' => ['people_density', 'high', 'overcrowding', 'population_density_risk', 'population_exposure_hazard', 'high', 'High population density'],
            'housing_material' => ['housing_material', 'light_materials', 'flammable', 'housing_material_risk', 'structural_hazard', 'light_materials', 'Combustible housing materials'],
            'housing_spacing' => ['housing_spacing', 'dikit_dikit', 'overcrowding', 'housing_spacing_risk', 'structural_hazard', 'dikit_dikit', 'Closely spaced structures'],
            'electrical' => ['electrical_risk', 'overflow', 'electrical', 'electrical_load_risk', 'electrical_hazard', 'overloaded_electrical_usage', 'Electrical overload exposure'],
            'wiring' => ['wiring', 'octopus', 'electrical', 'electrical_load_risk', 'electrical_hazard', 'octopus_wiring_area', 'Unsafe or octopus wiring'],
            'flammable' => ['housing_material', 'light_materials', 'flammable', 'combustible_storage_risk', 'flammable_material_hazard', 'heavy_combustible_buildup', 'Flammable material exposure'],
        ];
        [$riskFactor, $mode, $hazardType, $riskType, $hazardCategory, $conditionCode, $conditionLabel] = $profiles[$classification];

        if ($category === 'hazard') {
            $conditionCode = match ($classification) {
                'people_density' => 'high_population_density',
                'housing_material', 'housing_spacing' => 'houses_too_close_together',
                'electrical' => 'overloaded_outlets',
                'wiring' => 'octopus_wiring',
                'flammable' => 'large_amounts_of_paper_cardboard',
            };
        }

        return [
            'risk_factor' => $riskFactor,
            'mode' => $mode,
            'hazard_type' => $hazardType,
            'risk_type' => $category === 'risk' ? $riskType : null,
            'hazard_category' => $category === 'hazard' ? $hazardCategory : null,
            'condition_code' => $conditionCode,
            'condition_label' => $conditionLabel,
        ];
    }

    public function getCount(Request $request)
    {
        try {
            $count = Marker::count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'total' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function latest()
    {
        $latestMarker = Marker::query()
            ->orderByDesc('updated_at')
            ->orderByDesc('marker_id')
            ->first();

        return response()->json([
            'marker_id' => $latestMarker?->marker_id,
            'updated_at' => $latestMarker?->updated_at?->toISOString(),
            'count' => Marker::count(),
        ]);
    }

    public function update(Request $request, $markerId)
    {
        try {
            $marker = Marker::findOrFail($markerId);

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => ['nullable', Rule::in(self::MARKER_CATEGORIES)],
                'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],

            ]);

            $updateData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ];

            $resolvedCategory = $this->resolveCategory(
                $validated['category'] ?? null,

            );
            if ($resolvedCategory) {
                $updateData['category'] = $resolvedCategory;
            }

            if (array_key_exists('status', $validated) || array_key_exists('status_id', $validated)) {
                $updateData['status'] = $this->resolveStatus(
                    $validated['status'] ?? null,

                );
            }

            $marker->update($updateData);

            // Refresh the marker to get updated data
            $marker->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Marker updated successfully',
                'data' => $marker,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating marker: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateLocation(Request $request, int $markerId, BoundaryMatcherService $boundaryMatcherService)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $marker = Marker::query()->findOrFail($markerId);
        $lat = round((float) $validated['lat'], 7);
        $lng = round((float) $validated['lng'], 7);
        $isRiskOrHazard = in_array(strtolower((string) $marker->category), ['risk', 'hazard'], true)
            || $marker->riskAreas()->exists();
        $matchedHall = $isRiskOrHazard
            ? $boundaryMatcherService->locateBarangayHall($lat, $lng)
            : null;

        if ($isRiskOrHazard && ! $matchedHall) {
            throw ValidationException::withMessages([
                'lat' => 'Place the risk or hazard marker inside a saved barangay boundary with a linked barangay hall.',
            ]);
        }

        DB::transaction(function () use ($marker, $lat, $lng, $matchedHall) {
            $marker->update(['lat' => $lat, 'lng' => $lng]);

            if ($matchedHall) {
                $marker->riskAreas()->update([
                    'barangay_hall_id' => $matchedHall->barangay_hall_id,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Marker location updated successfully',
            'data' => $marker->fresh(),
        ]);
    }

    public function destroy($id)
    {
        try {
            \Log::info('Attempting to mark marker as spam', ['marker_id' => $id]);

            $marker = Marker::find($id);

            if (! $marker) {
                \Log::warning('Marker not found', ['id' => $id]);

                return response()->json([
                    'success' => false,
                    'message' => 'Marker not found',
                ], 404);
            }

            \Log::info('Found marker', [
                'marker_id' => $marker->marker_id,
                'title' => $marker->title,
            ]);

            $stationCount = $marker->stations()->count();
            $hydrantCount = $marker->hydrants()->count();
            $incidentCount = $marker->incidents()->count();

            \Log::info('Related records count', [
                'stations' => $stationCount,
                'hydrants' => $hydrantCount,
                'incidents' => $incidentCount,
            ]);

            $updated = $marker->update($this->markerSpamAuditUpdates(Auth::id()));

            if ($updated) {
                if (auth()->check() && $incidentCount > 0) {
                    UserActivityLogger::log('marked incident marker as spam', auth()->id(), request());
                }

                Cache::forget('map:markers:index:v2');
                Cache::forget('map:markers:index:v3_public');
                \Log::info('Marker marked as spam successfully');

                return response()->json([
                    'success' => true,
                    'message' => 'Marker marked as spam and hidden from the map.',
                ], 200);
            } else {
                \Log::error('Marker spam update failed - update() returned false');

                return response()->json([
                    'success' => false,
                    'message' => 'Marker could not be marked as spam.',
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Exception while marking marker as spam', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error marking marker as spam: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update marker status (soft delete or mark as inactive)
     */
    public function updateStatus(Request $request, $markerId)
    {
        try {
            $validated = $request->validate([
                'status' => ['nullable', Rule::in(self::MARKER_STATUSES)],
                'status_id' => 'nullable|exists:statuses,status_id',
            ]);

            $marker = Marker::find($markerId);

            if (! $marker) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marker not found',
                ], 404);
            }

            $marker->status = $this->resolveStatus(
                $validated['status'] ?? null,
                $validated['status_id'] ?? null
            );
            $marker->save();

            \Log::info('Marker status updated', [
                'marker_id' => $markerId,
                'new_status' => $marker->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Marker status updated successfully',
                'data' => $marker,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating marker status', [
                'marker_id' => $markerId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating marker status: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getMapData()
    {
        $markers = Marker::with('category', 'status')->get();

        return response()->json($markers);
    }
}
