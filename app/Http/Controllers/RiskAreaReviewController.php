<?php

namespace App\Http\Controllers;

use App\Models\RiskArea;
use App\Models\RiskAreaPhoto;
use App\Models\StationOfficer;
use App\Support\BarangayNotificationService;
use App\Support\MediaPath;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RiskAreaReviewController extends Controller
{
    public function adminIndex(Request $request): View
    {
        return $this->renderQueue($request, 'admin');
    }

    public function chiefIndex(Request $request): View
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::id())
            ->value('station_id');

        return $this->renderQueue($request, 'chief', $stationId);
    }

    public function adminReview(Request $request, int $riskAreaId): RedirectResponse
    {
        $riskArea = $this->resolveRiskAreaForAdmin($riskAreaId);

        return $this->handleReview($request, $riskArea, 'admin.risk-reviews.index');
    }

    public function chiefReview(Request $request, int $riskAreaId): RedirectResponse
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::id())
            ->value('station_id');

        $riskArea = $this->resolveRiskAreaForChief($riskAreaId, $stationId);

        return $this->handleReview($request, $riskArea, 'station-admin.risk-reviews.index');
    }

    public function adminUpdateActive(Request $request, int $riskAreaId): RedirectResponse
    {
        $riskArea = $this->resolveRiskAreaForAdmin($riskAreaId);

        return $this->handleActiveUpdate($request, $riskArea, 'admin.risk-reviews.index');
    }

    public function chiefUpdateActive(Request $request, int $riskAreaId): RedirectResponse
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::id())
            ->value('station_id');

        $riskArea = $this->resolveRiskAreaForChief($riskAreaId, $stationId);

        return $this->handleActiveUpdate($request, $riskArea, 'station-admin.risk-reviews.index');
    }

    public function adminDeleteActive(Request $request, int $riskAreaId): RedirectResponse
    {
        $riskArea = $this->resolveRiskAreaForAdmin($riskAreaId);

        return $this->handleActiveDelete($request, $riskArea, 'admin.risk-reviews.index');
    }

    public function chiefDeleteActive(Request $request, int $riskAreaId): RedirectResponse
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::id())
            ->value('station_id');

        $riskArea = $this->resolveRiskAreaForChief($riskAreaId, $stationId);

        return $this->handleActiveDelete($request, $riskArea, 'station-admin.risk-reviews.index');
    }

    protected function renderQueue(Request $request, string $mode, ?int $stationId = null): View
    {
        $statusFilter = strtolower((string) $request->string('review_status', 'pending'));
        $typeFilter = strtolower((string) $request->string('entry_type', 'all'));
        $reviewTypeFilter = strtolower((string) $request->string('review_type', 'all'));
        $search = trim((string) $request->string('search', ''));

        $baseQuery = RiskArea::query()
            ->with([
                'marker',
                'photos',
                'station',
                'reviewer',
                'reporter',
                'barangayHall.barangay.city',
            ]);

        if ($mode === 'chief') {
            $baseQuery->where('station_id', $stationId ?: 0);
        }

        $summaryCounts = (clone $baseQuery)
            ->selectRaw('review_status, COUNT(*) as total')
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        $reviewQueue = (clone $baseQuery)
            ->where(function ($query) {
                $query
                    ->where('review_status', '!=', 'approved')
                    ->orWhere('review_type', 'delete');
            })
            ->when(in_array($statusFilter, ['pending', 'approved', 'rejected'], true), function ($query) use ($statusFilter) {
                $query->where('review_status', $statusFilter);
            })
            ->when(in_array($typeFilter, ['hazard', 'risk'], true), function ($query) use ($typeFilter) {
                $query->where('entry_type', $typeFilter);
            })
            ->when(in_array($reviewTypeFilter, ['create', 'update', 'delete'], true), function ($query) use ($reviewTypeFilter) {
                $query->where('review_type', $reviewTypeFilter);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->whereHas('marker', function ($markerQuery) use ($search) {
                            $markerQuery
                                ->where('title', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        })
                        ->orWhere('street_or_sitio', 'like', '%' . $search . '%')
                        ->orWhere('risk_type', 'like', '%' . $search . '%')
                        ->orWhere('hazard_category', 'like', '%' . $search . '%')
                        ->orWhere('condition_label', 'like', '%' . $search . '%')
                        ->orWhere('remarks', 'like', '%' . $search . '%')
                        ->orWhereHas('barangayHall.barangay', function ($barangayQuery) use ($search) {
                            $barangayQuery->where('barangay_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('station', function ($stationQuery) use ($search) {
                            $stationQuery->where('station_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('updated_at')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        $activeEntries = (clone $baseQuery)
            ->where('review_status', 'approved')
            ->where('review_type', '!=', 'delete')
            ->when(in_array($typeFilter, ['hazard', 'risk'], true), function ($query) use ($typeFilter) {
                $query->where('entry_type', $typeFilter);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->whereHas('marker', function ($markerQuery) use ($search) {
                            $markerQuery
                                ->where('title', 'like', '%' . $search . '%')
                                ->orWhere('description', 'like', '%' . $search . '%');
                        })
                        ->orWhere('street_or_sitio', 'like', '%' . $search . '%')
                        ->orWhere('risk_type', 'like', '%' . $search . '%')
                        ->orWhere('hazard_category', 'like', '%' . $search . '%')
                        ->orWhere('condition_label', 'like', '%' . $search . '%')
                        ->orWhere('remarks', 'like', '%' . $search . '%')
                        ->orWhereHas('barangayHall.barangay', function ($barangayQuery) use ($search) {
                            $barangayQuery->where('barangay_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('station', function ($stationQuery) use ($search) {
                            $stationQuery->where('station_name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('updated_at')
            ->latest('created_at')
            ->get();

        return view('reviews.risk-area-queue', [
            'mode' => $mode,
            'activeNav' => 'risk-reviews',
            'reviewQueue' => $reviewQueue,
            'activeEntries' => $activeEntries,
            'filters' => [
                'review_status' => $statusFilter,
                'entry_type' => $typeFilter,
                'review_type' => $reviewTypeFilter,
                'search' => $search,
            ],
            'summaryCounts' => [
                'all' => (int) (clone $baseQuery)->count(),
                'pending' => (int) ($summaryCounts['pending'] ?? 0),
                'approved' => (int) ($summaryCounts['approved'] ?? 0),
                'rejected' => (int) ($summaryCounts['rejected'] ?? 0),
            ],
            'pageTitle' => $mode === 'admin'
                ? 'Hazard & Risk Review Queue'
                : 'Station Hazard & Risk Review Queue',
            'pageSubtitle' => $mode === 'admin'
                ? 'Review barangay-submitted hazard and risk requests across the system.'
                : 'Review hazard and risk requests assigned to your station before they move forward.',
            'indexRouteName' => $mode === 'admin'
                ? 'admin.risk-reviews.index'
                : 'station-admin.risk-reviews.index',
            'reviewRouteName' => $mode === 'admin'
                ? 'admin.risk-reviews.review'
                : 'station-admin.risk-reviews.review',
            'activeUpdateRouteName' => $mode === 'admin'
                ? 'admin.risk-reviews.active.update'
                : 'station-admin.risk-reviews.active.update',
            'activeDeleteRouteName' => $mode === 'admin'
                ? 'admin.risk-reviews.active.delete'
                : 'station-admin.risk-reviews.active.delete',
            'stations' => \App\Models\Station::query()->orderBy('station_name')->get(),
            'riskTypeOptions' => $this->riskTypeOptions(),
            'riskTypeGroups' => $this->riskTypeGroups(),
            'hazardTypeOptions' => $this->hazardTypeOptions(),
            'riskConditionOptions' => $this->riskConditionOptions(),
            'hazardConditionOptions' => $this->hazardConditionOptions(),
            'hazardTypeIcons' => $this->hazardTypeIcons(),
            'riskTypeIcons' => $this->riskTypeIcons(),
            'sidebarPartial' => $mode === 'admin'
                ? 'admin.partials.sidebar'
                : 'chief.partials.sidebar',
        ]);
    }

    protected function resolveRiskAreaForAdmin(int $riskAreaId): RiskArea
    {
        return RiskArea::query()
            ->with(['marker', 'photos'])
            ->findOrFail($riskAreaId);
    }

    protected function resolveRiskAreaForChief(int $riskAreaId, ?int $stationId): RiskArea
    {
        return RiskArea::query()
            ->with(['marker', 'photos'])
            ->where('station_id', $stationId ?: 0)
            ->findOrFail($riskAreaId);
    }

    protected function handleReview(Request $request, RiskArea $riskArea, string $redirectRoute): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
            'final_entry_type' => ['nullable', Rule::in(['hazard', 'risk'])],
            'final_risk_type' => ['nullable', 'string', Rule::in(array_keys($this->riskTypeOptions()))],
            'final_hazard_category' => ['nullable', 'string', Rule::in(array_keys($this->hazardTypeOptions()))],
            'final_condition_code' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($validated, $riskArea) {
            if ($validated['decision'] === 'approved' && ($riskArea->review_type ?? 'create') === 'delete') {
                $this->deleteRiskArea($riskArea);

                return;
            }

            $classificationUpdates = [];

            if ($validated['decision'] === 'approved') {
                $classification = [
                    'entry_type' => $validated['final_entry_type'] ?? ($riskArea->entry_type ?: 'risk'),
                    'risk_type' => $validated['final_risk_type'] ?? $riskArea->risk_type,
                    'hazard_category' => $validated['final_hazard_category'] ?? $riskArea->hazard_category,
                    'condition_code' => $validated['final_condition_code'] ?? $riskArea->condition_code,
                ];

                $this->assertRiskAreaSelectionValidity($classification);

                $classificationUpdates = [
                    'entry_type' => $classification['entry_type'],
                    'hazard_category' => $classification['entry_type'] === 'hazard' ? $classification['hazard_category'] : null,
                    'risk_type' => $classification['entry_type'] === 'risk' ? $classification['risk_type'] : null,
                    'risk_factor' => $this->deriveRiskFactor($classification['entry_type'], $classification['risk_type'], $classification['hazard_category']),
                    'mode' => $this->deriveLegacyMode($classification['entry_type'], $classification['condition_code'], $classification['hazard_category']),
                    'condition_code' => $classification['condition_code'],
                    'condition_label' => $this->resolveConditionLabel($classification['entry_type'], $classification['risk_type'], $classification['hazard_category'], $classification['condition_code']),
                    'hazard_type' => $this->deriveLegacyHazardType($classification['entry_type'], $classification['hazard_category']),
                    'status' => 'verified',
                ];

                $riskArea->marker?->update([
                    'category' => $this->deriveMarkerCategory($classification['entry_type']),
                    'status' => 'active',
                ]);
            }

            $riskArea->update([
                'review_status' => $validated['decision'],
                'review_notes' => $validated['review_notes'] ?: null,
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
            ] + $classificationUpdates);
        });

        Cache::forget('map:markers:index:v2');

        BarangayNotificationService::notifyRiskAreaReviewed($riskArea->fresh(['barangayHall.barangay']), $validated['decision']);

        $message = $validated['decision'] === 'approved'
            ? 'The hazard or risk request has been approved.'
            : 'The hazard or risk request has been rejected.';

        return redirect()
            ->route($redirectRoute, $request->only(['review_status', 'entry_type', 'review_type', 'search', 'page']))
            ->with('status', $message);
    }

    protected function handleActiveUpdate(Request $request, RiskArea $riskArea, string $redirectRoute): RedirectResponse
    {
        $validated = $request->validate($this->activeRiskAreaValidationRules());
        $this->assertRiskAreaSelectionValidity($validated);
        $structuredNotes = $this->buildRiskAreaStructuredNotes($validated);
        $riskFactor = $this->deriveRiskFactor($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null);
        $conditionLabel = $this->resolveConditionLabel($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null, $validated['condition_code']);
        $legacyHazardType = $this->deriveLegacyHazardType($validated['entry_type'], $validated['hazard_category'] ?? null);
        $legacyMode = $this->deriveLegacyMode($validated['entry_type'], $validated['condition_code'], $validated['hazard_category'] ?? null);
        $markerCategory = $this->deriveMarkerCategory($validated['entry_type']);

        DB::transaction(function () use ($validated, $riskArea, $request, $structuredNotes, $riskFactor, $conditionLabel, $legacyHazardType, $legacyMode, $markerCategory) {
            $riskArea->marker?->update([
                'title' => $validated['title'],
                'description' => trim($validated['location_description'] . ($structuredNotes !== '' ? "\n\n" . $structuredNotes : '')),
                'status' => $validated['visibility'],
                'category' => $markerCategory,
            ]);

            $riskArea->update([
                'station_id' => $validated['station_id'] ?? null,
                'entry_type' => $validated['entry_type'],
                'hazard_category' => $validated['hazard_category'] ?? null,
                'risk_type' => $validated['risk_type'] ?? null,
                'street_or_sitio' => $validated['street_or_sitio'] ?? null,
                'risk_factor' => $riskFactor,
                'mode' => $legacyMode,
                'condition_code' => $validated['condition_code'],
                'condition_label' => $conditionLabel,
                'hazard_type' => $legacyHazardType,
                'status' => $validated['status'],
                'population_density_level' => $validated['population_density_level'] ?? null,
                'building_density_level' => $validated['building_density_level'] ?? null,
                'road_accessibility' => $validated['road_accessibility'] ?? null,
                'distance_to_hydrant' => $validated['distance_to_hydrant'] ?? null,
                'distance_to_water_source' => $validated['distance_to_water_source'] ?? null,
                'nearby_establishments' => $validated['nearby_establishments'] ?? null,
                'estimated_affected_households' => $validated['estimated_affected_households'] ?? null,
                'risk_score' => $validated['risk_score'] ?? null,
                'assessment_level' => $validated['assessment_level'] ?? null,
                'assessment_description' => $validated['assessment_description'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'near_hydrant' => (bool) ($validated['near_hydrant'] ?? false),
                'near_water_source' => (bool) ($validated['near_water_source'] ?? false),
                'blocked_hydrant' => (bool) ($validated['blocked_hydrant'] ?? false),
                'informal_settlement' => (bool) ($validated['informal_settlement'] ?? false),
                'cooking_fire_prone_activity' => (bool) ($validated['cooking_fire_prone_activity'] ?? false),
                'combustible_materials_present' => (bool) ($validated['combustible_materials_present'] ?? false),
                'poor_waste_disposal' => (bool) ($validated['poor_waste_disposal'] ?? false),
                'previous_fire_prone_location' => (bool) ($validated['previous_fire_prone_location'] ?? false),
                'review_status' => 'approved',
                'review_type' => 'update',
                'review_notes' => 'Live record updated by ' . strtolower((string) (Auth::user()?->role->role ?? 'reviewer')) . '.',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
            ]);

            foreach ($request->file('photos', []) as $photo) {
                if (! $photo) {
                    continue;
                }

                $storedPath = MediaPath::storeForDisk(
                    $photo->store('risk-areas', 'supabase'),
                    'supabase'
                );

                RiskAreaPhoto::create([
                    'risk_area_id' => $riskArea->risk_area_id,
                    'photo_path' => $storedPath,
                    'photo_name' => $photo->getClientOriginalName(),
                ]);
            }
        });

        BarangayNotificationService::notifyRiskAreaReviewed($riskArea->fresh(['barangayHall.barangay']), 'approved');

        return redirect()
            ->route($redirectRoute, $request->only(['review_status', 'entry_type', 'review_type', 'search', 'page']))
            ->with('status', 'The live hazard or risk entry has been updated.');
    }

    protected function activeRiskAreaValidationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'location_description' => ['required', 'string', 'max:1000'],
            'entry_type' => ['required', Rule::in(['hazard', 'risk'])],
            'risk_type' => ['nullable', 'string', Rule::in(array_keys($this->riskTypeOptions()))],
            'hazard_category' => ['nullable', 'string', Rule::in(array_keys($this->hazardTypeOptions()))],
            'street_or_sitio' => ['nullable', 'string', 'max:255'],
            'condition_code' => ['required', 'string', 'max:120'],
            'population_density_level' => ['nullable', Rule::in(['low', 'moderate', 'high'])],
            'building_density_level' => ['nullable', Rule::in(['low', 'moderate', 'high'])],
            'road_accessibility' => ['nullable', Rule::in(['easy', 'moderate', 'difficult', 'very_difficult'])],
            'distance_to_hydrant' => ['nullable', 'numeric', 'between:0,50000'],
            'distance_to_water_source' => ['nullable', 'numeric', 'between:0,50000'],
            'nearby_establishments' => ['nullable', 'string', 'max:2000'],
            'estimated_affected_households' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['pending', 'verified', 'resolved'])],
            'station_id' => ['nullable', 'exists:stations,station_id'],
            'risk_score' => ['nullable', 'numeric', 'between:0,100'],
            'assessment_level' => ['nullable', Rule::in(['low', 'moderate', 'high'])],
            'assessment_description' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'near_hydrant' => ['nullable', 'boolean'],
            'near_water_source' => ['nullable', 'boolean'],
            'blocked_hydrant' => ['nullable', 'boolean'],
            'informal_settlement' => ['nullable', 'boolean'],
            'cooking_fire_prone_activity' => ['nullable', 'boolean'],
            'combustible_materials_present' => ['nullable', 'boolean'],
            'poor_waste_disposal' => ['nullable', 'boolean'],
            'previous_fire_prone_location' => ['nullable', 'boolean'],
            'visibility' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function riskTypeOptions(): array
    {
        return [
            'housing_material_risk' => 'Housing material risk',
            'housing_spacing_risk' => 'Housing spacing risk',
            'population_density_risk' => 'Population density risk',
            'road_accessibility_risk' => 'Road accessibility risk',
            'hydrant_coverage_risk' => 'Hydrant coverage risk',
            'water_source_access_risk' => 'Water source access risk',
            'informal_settlement_risk' => 'Informal settlement risk',
            'combustible_storage_risk' => 'Combustible storage risk',
            'occupancy_exposure_risk' => 'Occupancy exposure risk',
            'historical_fire_prone_risk' => 'Historical fire-prone risk',
            'weather_seasonal_risk' => 'Weather / seasonal risk',
            'response_capability_risk' => 'Response capability risk',
            'electrical_load_risk' => 'Electrical load risk',
            'cooking_activity_risk' => 'Cooking activity risk',
        ];
    }

    protected function riskTypeGroups(): array
    {
        return [
            'Construction & Layout' => ['housing_material_risk', 'housing_spacing_risk', 'informal_settlement_risk', 'occupancy_exposure_risk'],
            'Population & Exposure' => ['population_density_risk', 'historical_fire_prone_risk', 'weather_seasonal_risk'],
            'Utilities & Fire Load' => ['electrical_load_risk', 'cooking_activity_risk', 'combustible_storage_risk'],
            'Access & Water Supply' => ['road_accessibility_risk', 'hydrant_coverage_risk', 'water_source_access_risk'],
            'Emergency Response' => ['response_capability_risk'],
        ];
    }

    protected function hazardTypeOptions(): array
    {
        return [
            'electrical_hazard' => 'Electrical Hazard',
            'lpg_gas_hazard' => 'LPG / Gas Hazard',
            'structural_hazard' => 'Structural Hazard',
            'flammable_material_hazard' => 'Flammable Material Hazard',
            'vegetation_environmental_hazard' => 'Vegetation / Environmental Hazard',
            'human_behavior_hazard' => 'Human Behavior Hazard',
            'cooking_hazard' => 'Cooking Hazard',
            'commercial_establishment_hazard' => 'Commercial Establishment Hazard',
            'special_event_hazard' => 'Special Event Hazard',
            'response_capability_hazard' => 'Response Capability Hazard',
            'population_exposure_hazard' => 'Population Exposure Hazard',
            'waste_management_hazard' => 'Waste Management Hazard',
            'seasonal_weather_hazard' => 'Seasonal / Weather Hazard',
            'occupancy_hazard' => 'Occupancy Hazard',
            'fire_protection_deficiency_hazard' => 'Fire Protection Deficiency Hazard',
        ];
    }

    protected function hazardTypeIcons(): array
    {
        return [
            'electrical_hazard' => 'fa-bolt',
            'lpg_gas_hazard' => 'fa-gas-pump',
            'structural_hazard' => 'fa-house-crack',
            'flammable_material_hazard' => 'fa-oil-can',
            'vegetation_environmental_hazard' => 'fa-seedling',
            'human_behavior_hazard' => 'fa-person-circle-exclamation',
            'cooking_hazard' => 'fa-kitchen-set',
            'commercial_establishment_hazard' => 'fa-store',
            'special_event_hazard' => 'fa-calendar-day',
            'response_capability_hazard' => 'fa-truck-medical',
            'population_exposure_hazard' => 'fa-people-group',
            'waste_management_hazard' => 'fa-trash',
            'seasonal_weather_hazard' => 'fa-sun',
            'occupancy_hazard' => 'fa-building-circle-exclamation',
            'fire_protection_deficiency_hazard' => 'fa-fire-extinguisher',
        ];
    }

    protected function riskTypeIcons(): array
    {
        return [
            'housing_material_risk' => 'fa-house',
            'housing_spacing_risk' => 'fa-grip-lines-vertical',
            'population_density_risk' => 'fa-people-group',
            'road_accessibility_risk' => 'fa-road',
            'hydrant_coverage_risk' => 'fa-faucet-drip',
            'water_source_access_risk' => 'fa-water',
            'informal_settlement_risk' => 'fa-city',
            'combustible_storage_risk' => 'fa-boxes-stacked',
            'occupancy_exposure_risk' => 'fa-building-user',
            'historical_fire_prone_risk' => 'fa-clock-rotate-left',
            'weather_seasonal_risk' => 'fa-cloud-sun',
            'response_capability_risk' => 'fa-truck-fast',
            'electrical_load_risk' => 'fa-bolt',
            'cooking_activity_risk' => 'fa-fire-burner',
        ];
    }

    protected function riskConditionOptions(): array
    {
        return [
            'housing_material_risk' => ['light_materials' => 'Mostly light combustible materials', 'mixed' => 'Mixed housing materials', 'highly_combustible_construction' => 'Highly combustible construction'],
            'housing_spacing_risk' => ['dikit_dikit' => 'Dikit-dikit structures', 'narrow_fire_break' => 'Narrow or missing fire break', 'houses_too_close_together' => 'Houses built too close together'],
            'population_density_risk' => ['low' => 'Low population density', 'medium' => 'Moderate population density', 'high' => 'High population density'],
            'road_accessibility_risk' => ['one_truck_passable' => 'One fire truck passable', 'motorcycle_only' => 'Motorcycle or small vehicle only', 'foot_access_only' => 'Foot access only'],
            'hydrant_coverage_risk' => ['limited_hydrant_access' => 'Limited hydrant access', 'hydrant_too_far' => 'Hydrant too far from the area', 'no_usable_hydrant_nearby' => 'No usable hydrant nearby'],
            'water_source_access_risk' => ['near_water_source' => 'Water source available but limited', 'water_source_far' => 'Water source far from the area', 'no_reliable_water_source' => 'No reliable water source nearby'],
            'informal_settlement_risk' => ['light_materials' => 'Light-material houses', 'shared_utilities' => 'Shared utilities and unsafe service layout', 'dense_informal_cluster' => 'Dense informal settlement cluster'],
            'combustible_storage_risk' => ['mixed_combustible_storage' => 'Mixed combustible storage', 'household_fuel_storage' => 'Fuel or LPG stored in homes', 'heavy_combustible_buildup' => 'Heavy combustible buildup'],
            'occupancy_exposure_risk' => ['mixed_use_occupancy' => 'Mixed residential-commercial use', 'high_vulnerable_population' => 'High vulnerable population', 'beyond_intended_capacity' => 'Buildings used beyond intended capacity'],
            'historical_fire_prone_risk' => ['past_incident_within_area' => 'Past incident within the area', 'repeated_incident_history' => 'Repeated incident history', 'known_fire_prone_location' => 'Known fire-prone location'],
            'weather_seasonal_risk' => ['dry_season' => 'Dry season conditions', 'high_temperature_low_humidity' => 'High temperature and low humidity', 'strong_winds' => 'Strong winds that may accelerate spread'],
            'response_capability_risk' => ['distant_fire_station' => 'Distant fire station', 'insufficient_emergency_equipment' => 'Insufficient emergency equipment', 'poor_response_access' => 'Poor response access'],
            'electrical_load_risk' => ['octopus_wiring_area' => 'Octopus wiring observed in the area', 'overloaded_electrical_usage' => 'Overloaded electrical usage', 'unsafe_distribution_lines' => 'Unsafe distribution lines'],
            'cooking_activity_risk' => ['frequent_open_flame_cooking' => 'Frequent open-flame cooking', 'dense_cooking_activity_zone' => 'Dense cooking activity zone', 'unsafe_shared_cooking_area' => 'Unsafe shared cooking area'],
        ];
    }

    protected function hazardConditionOptions(): array
    {
        return [
            'electrical_hazard' => ['octopus_wiring' => 'Octopus wiring', 'exposed_wires' => 'Exposed wires', 'overloaded_outlets' => 'Overloaded outlets', 'illegal_electrical_connections' => 'Illegal electrical connections', 'damaged_extension_cords' => 'Damaged extension cords', 'improper_splicing' => 'Improper splicing', 'outdated_electrical_systems' => 'Outdated electrical systems'],
            'lpg_gas_hazard' => ['lpg_tanks_stored_indoors' => 'LPG tanks stored indoors', 'leaking_gas_odor' => 'Leaking gas odor', 'expired_lpg_tanks' => 'Expired LPG tanks', 'improper_hose_installation' => 'Improper hose installation', 'tanks_near_heat_sources' => 'Tanks placed near heat sources', 'no_safety_regulators' => 'No safety regulators'],
            'structural_hazard' => ['houses_too_close_together' => 'Houses built too close together', 'narrow_alleys_restricting_fire_truck_access' => 'Narrow alleys restricting fire truck access', 'absence_of_fire_exits' => 'Absence of fire exits', 'highly_combustible_materials' => 'Use of highly combustible materials', 'deteriorating_structures' => 'Deteriorating structures'],
            'flammable_material_hazard' => ['improper_storage_of_gasoline' => 'Improper storage of gasoline', 'paint_thinners_or_chemicals' => 'Paint thinners or chemicals', 'fireworks_storage' => 'Fireworks storage', 'large_amounts_of_paper_cardboard' => 'Large amounts of paper/cardboard', 'fuel_containers_inside_homes' => 'Fuel containers kept inside homes'],
            'vegetation_environmental_hazard' => ['dry_grass_accumulation' => 'Dry grass accumulation', 'unmanaged_vegetation_near_houses' => 'Unmanaged vegetation near houses', 'prolonged_drought_conditions' => 'Prolonged drought conditions', 'vacant_lots_with_combustible_waste' => 'Open vacant lots filled with combustible waste'],
            'human_behavior_hazard' => ['improper_disposal_of_cigarette_butts' => 'Improper disposal of cigarette butts', 'unattended_candles' => 'Unattended candles', 'children_playing_with_matches_or_lighters' => 'Children playing with matches/lighters', 'leaving_cooking_unattended' => 'Leaving cooking unattended', 'open_burning_of_garbage' => 'Open burning of garbage'],
            'cooking_hazard' => ['defective_stoves' => 'Defective stoves', 'grease_buildup' => 'Grease buildup', 'unattended_cooking' => 'Unattended cooking', 'leaking_gas_appliances' => 'Leaking gas appliances', 'improper_placement_of_cooking_equipment' => 'Improper placement of cooking equipment'],
            'commercial_establishment_hazard' => ['absence_of_fire_extinguishers' => 'Absence of fire extinguishers', 'blocked_emergency_exits' => 'Blocked emergency exits', 'overloaded_electrical_systems' => 'Overloaded electrical systems', 'lack_of_fire_safety_permits' => 'Lack of fire safety permits', 'overcrowded_establishments' => 'Overcrowded establishments'],
            'special_event_hazard' => ['unauthorized_fireworks_use' => 'Unauthorized fireworks use', 'temporary_electrical_installations' => 'Temporary electrical installations', 'festival_overcrowding' => 'Overcrowding during festivals', 'makeshift_cooking_areas' => 'Makeshift cooking areas'],
            'response_capability_hazard' => ['lack_of_hydrants' => 'Lack of hydrants', 'non_functional_hydrants' => 'Non-functional hydrants', 'poor_road_accessibility' => 'Poor road accessibility', 'distant_fire_stations' => 'Distant fire stations', 'insufficient_emergency_equipment' => 'Insufficient emergency equipment'],
            'population_exposure_hazard' => ['high_population_density' => 'High population density', 'informal_settlements' => 'Informal settlements', 'large_vulnerable_population' => 'Large vulnerable population', 'overcrowded_housing' => 'Overcrowded housing'],
            'waste_management_hazard' => ['accumulation_of_combustible_waste' => 'Accumulation of combustible waste', 'illegal_dumping' => 'Illegal dumping', 'improper_disposal_of_flammable_materials' => 'Improper disposal of flammable materials', 'burning_of_trash_near_residences' => 'Burning of trash near residences'],
            'seasonal_weather_hazard' => ['dry_season' => 'Dry season', 'high_temperature' => 'High temperature', 'low_humidity' => 'Low humidity', 'strong_winds' => 'Strong winds that may accelerate fire spread'],
            'occupancy_hazard' => ['buildings_used_beyond_capacity' => 'Buildings used beyond intended capacity', 'mixed_residential_commercial_use' => 'Mixed residential-commercial use', 'absence_of_evacuation_plans' => 'Absence of evacuation plans', 'lack_of_safety_orientation' => 'Lack of safety orientation'],
            'fire_protection_deficiency_hazard' => ['expired_fire_extinguishers' => 'Expired fire extinguishers', 'absence_of_smoke_detectors' => 'Absence of smoke detectors', 'missing_fire_alarms' => 'Missing fire alarms', 'no_evacuation_signage' => 'No evacuation signage', 'lack_of_emergency_lighting' => 'Lack of emergency lighting'],
        ];
    }

    protected function buildRiskAreaStructuredNotes(array $validated): string
    {
        return collect([
            !empty($validated['entry_type']) ? 'Entry Type: ' . ucfirst($validated['entry_type']) : null,
            !empty($validated['street_or_sitio']) ? 'Street/Sitio: ' . $validated['street_or_sitio'] : null,
            !empty($validated['risk_type']) ? 'Risk Type: ' . str_replace('_', ' ', $validated['risk_type']) : null,
            !empty($validated['hazard_category']) ? 'Hazard Type: ' . ($this->hazardTypeOptions()[$validated['hazard_category']] ?? str_replace('_', ' ', $validated['hazard_category'])) : null,
            !empty($validated['condition_code']) ? 'Condition: ' . ($this->resolveConditionLabel($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null, $validated['condition_code']) ?? str_replace('_', ' ', $validated['condition_code'])) : null,
            !empty($validated['assessment_level']) ? 'Assessment: ' . ucfirst($validated['assessment_level']) : null,
            !empty($validated['assessment_description']) ? 'Assessment Description: ' . $validated['assessment_description'] : null,
            !empty($validated['remarks']) ? 'Remarks: ' . $validated['remarks'] : null,
            !empty($validated['notes']) ? 'Notes: ' . $validated['notes'] : null,
        ])->filter()->implode("\n");
    }

    protected function assertRiskAreaSelectionValidity(array $validated): void
    {
        if (($validated['entry_type'] ?? null) === 'risk') {
            $riskType = $validated['risk_type'] ?? null;
            $conditionCode = $validated['condition_code'] ?? null;
            $allowed = $this->riskConditionOptions()[$riskType ?? ''] ?? null;

            if (! $riskType || ! is_array($allowed) || ! array_key_exists($conditionCode, $allowed)) {
                throw ValidationException::withMessages([
                    'condition_code' => 'Select a valid risk type and matching risk condition.',
                ]);
            }

            return;
        }

        $hazardType = $validated['hazard_category'] ?? null;
        $conditionCode = $validated['condition_code'] ?? null;
        $allowed = $this->hazardConditionOptions()[$hazardType ?? ''] ?? null;

        if (! $hazardType || ! is_array($allowed) || ! array_key_exists($conditionCode, $allowed)) {
            throw ValidationException::withMessages([
                'condition_code' => 'Select a valid hazard type and matching hazard condition.',
            ]);
        }
    }

    protected function deriveRiskFactor(string $entryType, ?string $riskType, ?string $hazardCategory): string
    {
        if ($entryType === 'hazard') {
            return match ($hazardCategory) {
                'electrical_hazard',
                'commercial_establishment_hazard' => 'electrical_risk',
                'population_exposure_hazard' => 'people_density',
                'structural_hazard',
                'occupancy_hazard' => 'housing_spacing',
                'flammable_material_hazard',
                'lpg_gas_hazard',
                'cooking_hazard',
                'waste_management_hazard' => 'housing_material',
                default => 'wiring',
            };
        }

        return match ($riskType) {
            'population_density_risk',
            'occupancy_exposure_risk' => 'people_density',
            'housing_spacing_risk',
            'road_accessibility_risk',
            'response_capability_risk' => 'housing_spacing',
            'housing_material_risk',
            'informal_settlement_risk',
            'combustible_storage_risk' => 'housing_material',
            'electrical_load_risk' => 'electrical_risk',
            default => 'wiring',
        };
    }

    protected function resolveConditionLabel(string $entryType, ?string $riskType, ?string $hazardCategory, ?string $conditionCode): ?string
    {
        if (! $conditionCode) {
            return null;
        }

        $catalog = $entryType === 'hazard'
            ? ($this->hazardConditionOptions()[$hazardCategory ?? ''] ?? [])
            : ($this->riskConditionOptions()[$riskType ?? ''] ?? []);

        return $catalog[$conditionCode] ?? str_replace('_', ' ', $conditionCode);
    }

    protected function deriveLegacyHazardType(string $entryType, ?string $hazardCategory): string
    {
        if ($entryType !== 'hazard') {
            return 'none';
        }

        return match ($hazardCategory) {
            'electrical_hazard',
            'commercial_establishment_hazard' => 'electrical',
            'population_exposure_hazard',
            'occupancy_hazard' => 'overcrowding',
            'lpg_gas_hazard',
            'flammable_material_hazard',
            'vegetation_environmental_hazard',
            'human_behavior_hazard',
            'cooking_hazard',
            'special_event_hazard',
            'waste_management_hazard',
            'seasonal_weather_hazard',
            'fire_protection_deficiency_hazard' => 'flammable',
            default => 'none',
        };
    }

    protected function deriveLegacyMode(string $entryType, ?string $conditionCode, ?string $hazardCategory = null): string
    {
        if ($entryType === 'risk') {
            return match ($conditionCode) {
                'low', 'medium', 'high', 'mixed', 'light_materials', 'dikit_dikit', 'moderate', 'far', 'none', 'octopus', 'straight', 'overflow' => $conditionCode,
                default => 'moderate',
            };
        }

        return match ($conditionCode) {
            'octopus_wiring', 'illegal_electrical_connections' => 'octopus',
            'exposed_wires', 'improper_splicing' => 'straight',
            'overloaded_outlets', 'overloaded_electrical_systems', 'temporary_electrical_installations' => 'overflow',
            'high_population_density', 'festival_overcrowding', 'overcrowded_establishments', 'overcrowded_housing' => 'high',
            'houses_too_close_together', 'narrow_alleys_restricting_fire_truck_access' => 'dikit_dikit',
            'highly_combustible_materials', 'accumulation_of_combustible_waste', 'dry_grass_accumulation' => 'light_materials',
            'poor_road_accessibility', 'distant_fire_stations' => 'far',
            'lack_of_hydrants', 'non_functional_hydrants' => 'none',
            default => match ($hazardCategory) {
                'seasonal_weather_hazard', 'human_behavior_hazard', 'special_event_hazard' => 'high',
                'response_capability_hazard' => 'far',
                default => 'moderate',
            },
        };
    }

    protected function deriveMarkerCategory(string $entryType): string
    {
        return $entryType === 'hazard' ? 'hazard' : 'risk';
    }

    protected function handleActiveDelete(Request $request, RiskArea $riskArea, string $redirectRoute): RedirectResponse
    {
        DB::transaction(function () use ($riskArea) {
            $this->deleteRiskArea($riskArea);
        });

        return redirect()
            ->route($redirectRoute, $request->only(['review_status', 'entry_type', 'review_type', 'search', 'page']))
            ->with('status', 'The live hazard or risk entry has been removed.');
    }

    protected function deleteRiskArea(RiskArea $riskArea): void
    {
        $riskArea->loadMissing(['marker', 'photos']);

        foreach ($riskArea->photos as $photo) {
            if (! empty($photo->photo_path)) {
                MediaPath::delete($photo->photo_path);
            }

            $photo->delete();
        }

        $marker = $riskArea->marker;

        $riskArea->delete();

        if ($marker) {
            $marker->delete();
        }
    }
}
