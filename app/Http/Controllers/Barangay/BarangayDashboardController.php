<?php

namespace App\Http\Controllers\Barangay;

use App\Http\Controllers\RiskAreaReviewController;
use App\Http\Controllers\Controller;
use App\Models\AiActionPlanChecklist;
use App\Models\Barangay;
use App\Models\BarangayFireRecord;
use App\Models\BarangayHall;
use App\Models\BarangayPredictionChecklist;
use App\Models\BoundaryLocation;
use App\Models\City;
use App\Models\CommunityActivityRequest;
use App\Models\FullAddress;
use App\Models\HistoricalFireIncident;
use App\Models\Incident;
use App\Models\IncidentContext;
use App\Models\Marker;
use App\Models\MitigationAction;
use App\Models\Post;
use App\Models\RiskArea;
use App\Models\RiskAreaPhoto;
use App\Models\Station;
use App\Models\User;
use App\Support\BarangayNotificationService;
use App\Support\IncidentContextDispatcher;
use App\Support\HistoricalFireIncidentSynchronizer;
use App\Support\MediaPath;
use App\Support\UserActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BarangayDashboardController extends Controller
{
    private const RISK_HAZARD_REPORTS_DIRECTORY = 'riskhazard';
    private const RISK_HAZARD_REPORTS_MAX_FILES = 3;
    private const RISK_HAZARD_REPORTS_MAX_TOTAL_BYTES = 5242880;

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
            'Construction & Layout' => [
                'housing_material_risk',
                'housing_spacing_risk',
                'informal_settlement_risk',
                'occupancy_exposure_risk',
            ],
            'Population & Exposure' => [
                'population_density_risk',
                'historical_fire_prone_risk',
                'weather_seasonal_risk',
            ],
            'Utilities & Fire Load' => [
                'electrical_load_risk',
                'cooking_activity_risk',
                'combustible_storage_risk',
            ],
            'Access & Water Supply' => [
                'road_accessibility_risk',
                'hydrant_coverage_risk',
                'water_source_access_risk',
            ],
            'Emergency Response' => [
                'response_capability_risk',
            ],
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

    protected function riskConditionOptions(): array
    {
        return [
            'housing_material_risk' => [
                'light_materials' => 'Mostly light combustible materials',
                'mixed' => 'Mixed housing materials',
                'highly_combustible_construction' => 'Highly combustible construction',
            ],
            'housing_spacing_risk' => [
                'dikit_dikit' => 'Dikit-dikit structures',
                'narrow_fire_break' => 'Narrow or missing fire break',
                'houses_too_close_together' => 'Houses built too close together',
            ],
            'population_density_risk' => [
                'low' => 'Low population density',
                'medium' => 'Moderate population density',
                'high' => 'High population density',
            ],
            'road_accessibility_risk' => [
                'one_truck_passable' => 'One fire truck passable',
                'motorcycle_only' => 'Motorcycle or small vehicle only',
                'foot_access_only' => 'Foot access only',
            ],
            'hydrant_coverage_risk' => [
                'limited_hydrant_access' => 'Limited hydrant access',
                'hydrant_too_far' => 'Hydrant too far from the area',
                'no_usable_hydrant_nearby' => 'No usable hydrant nearby',
            ],
            'water_source_access_risk' => [
                'near_water_source' => 'Water source available but limited',
                'water_source_far' => 'Water source far from the area',
                'no_reliable_water_source' => 'No reliable water source nearby',
            ],
            'informal_settlement_risk' => [
                'light_materials' => 'Light-material houses',
                'shared_utilities' => 'Shared utilities and unsafe service layout',
                'dense_informal_cluster' => 'Dense informal settlement cluster',
            ],
            'combustible_storage_risk' => [
                'mixed_combustible_storage' => 'Mixed combustible storage',
                'household_fuel_storage' => 'Fuel or LPG stored in homes',
                'heavy_combustible_buildup' => 'Heavy combustible buildup',
            ],
            'occupancy_exposure_risk' => [
                'mixed_use_occupancy' => 'Mixed residential-commercial use',
                'high_vulnerable_population' => 'High vulnerable population',
                'beyond_intended_capacity' => 'Buildings used beyond intended capacity',
            ],
            'historical_fire_prone_risk' => [
                'past_incident_within_area' => 'Past incident within the area',
                'repeated_incident_history' => 'Repeated incident history',
                'known_fire_prone_location' => 'Known fire-prone location',
            ],
            'weather_seasonal_risk' => [
                'dry_season' => 'Dry season conditions',
                'high_temperature_low_humidity' => 'High temperature and low humidity',
                'strong_winds' => 'Strong winds that may accelerate spread',
            ],
            'response_capability_risk' => [
                'distant_fire_station' => 'Distant fire station',
                'insufficient_emergency_equipment' => 'Insufficient emergency equipment',
                'poor_response_access' => 'Poor response access',
            ],
            'electrical_load_risk' => [
                'octopus_wiring_area' => 'Octopus wiring observed in the area',
                'overloaded_electrical_usage' => 'Overloaded electrical usage',
                'unsafe_distribution_lines' => 'Unsafe distribution lines',
            ],
            'cooking_activity_risk' => [
                'frequent_open_flame_cooking' => 'Frequent open-flame cooking',
                'dense_cooking_activity_zone' => 'Dense cooking activity zone',
                'unsafe_shared_cooking_area' => 'Unsafe shared cooking area',
            ],
        ];
    }

    protected function hazardConditionOptions(): array
    {
        return [
            'electrical_hazard' => [
                'octopus_wiring' => 'Octopus wiring',
                'exposed_wires' => 'Exposed wires',
                'overloaded_outlets' => 'Overloaded outlets',
                'illegal_electrical_connections' => 'Illegal electrical connections',
                'damaged_extension_cords' => 'Damaged extension cords',
                'improper_splicing' => 'Improper splicing',
                'outdated_electrical_systems' => 'Outdated electrical systems',
            ],
            'lpg_gas_hazard' => [
                'lpg_tanks_stored_indoors' => 'LPG tanks stored indoors',
                'leaking_gas_odor' => 'Leaking gas odor',
                'expired_lpg_tanks' => 'Expired LPG tanks',
                'improper_hose_installation' => 'Improper hose installation',
                'tanks_near_heat_sources' => 'Tanks placed near heat sources',
                'no_safety_regulators' => 'No safety regulators',
            ],
            'structural_hazard' => [
                'houses_too_close_together' => 'Houses built too close together',
                'narrow_alleys_restricting_fire_truck_access' => 'Narrow alleys restricting fire truck access',
                'absence_of_fire_exits' => 'Absence of fire exits',
                'highly_combustible_materials' => 'Use of highly combustible materials',
                'deteriorating_structures' => 'Deteriorating structures',
            ],
            'flammable_material_hazard' => [
                'improper_storage_of_gasoline' => 'Improper storage of gasoline',
                'paint_thinners_or_chemicals' => 'Paint thinners or chemicals',
                'fireworks_storage' => 'Fireworks storage',
                'large_amounts_of_paper_cardboard' => 'Large amounts of paper/cardboard',
                'fuel_containers_inside_homes' => 'Fuel containers kept inside homes',
            ],
            'vegetation_environmental_hazard' => [
                'dry_grass_accumulation' => 'Dry grass accumulation',
                'unmanaged_vegetation_near_houses' => 'Unmanaged vegetation near houses',
                'prolonged_drought_conditions' => 'Prolonged drought conditions',
                'vacant_lots_with_combustible_waste' => 'Open vacant lots filled with combustible waste',
            ],
            'human_behavior_hazard' => [
                'improper_disposal_of_cigarette_butts' => 'Improper disposal of cigarette butts',
                'unattended_candles' => 'Unattended candles',
                'children_playing_with_matches_or_lighters' => 'Children playing with matches/lighters',
                'leaving_cooking_unattended' => 'Leaving cooking unattended',
                'open_burning_of_garbage' => 'Open burning of garbage',
            ],
            'cooking_hazard' => [
                'defective_stoves' => 'Defective stoves',
                'grease_buildup' => 'Grease buildup',
                'unattended_cooking' => 'Unattended cooking',
                'leaking_gas_appliances' => 'Leaking gas appliances',
                'improper_placement_of_cooking_equipment' => 'Improper placement of cooking equipment',
            ],
            'commercial_establishment_hazard' => [
                'absence_of_fire_extinguishers' => 'Absence of fire extinguishers',
                'blocked_emergency_exits' => 'Blocked emergency exits',
                'overloaded_electrical_systems' => 'Overloaded electrical systems',
                'lack_of_fire_safety_permits' => 'Lack of fire safety permits',
                'overcrowded_establishments' => 'Overcrowded establishments',
            ],
            'special_event_hazard' => [
                'unauthorized_fireworks_use' => 'Unauthorized fireworks use',
                'temporary_electrical_installations' => 'Temporary electrical installations',
                'festival_overcrowding' => 'Overcrowding during festivals',
                'makeshift_cooking_areas' => 'Makeshift cooking areas',
            ],
            'response_capability_hazard' => [
                'lack_of_hydrants' => 'Lack of hydrants',
                'non_functional_hydrants' => 'Non-functional hydrants',
                'poor_road_accessibility' => 'Poor road accessibility',
                'distant_fire_stations' => 'Distant fire stations',
                'insufficient_emergency_equipment' => 'Insufficient emergency equipment',
            ],
            'population_exposure_hazard' => [
                'high_population_density' => 'High population density',
                'informal_settlements' => 'Informal settlements',
                'large_vulnerable_population' => 'Large vulnerable population',
                'overcrowded_housing' => 'Overcrowded housing',
            ],
            'waste_management_hazard' => [
                'accumulation_of_combustible_waste' => 'Accumulation of combustible waste',
                'illegal_dumping' => 'Illegal dumping',
                'improper_disposal_of_flammable_materials' => 'Improper disposal of flammable materials',
                'burning_of_trash_near_residences' => 'Burning of trash near residences',
            ],
            'seasonal_weather_hazard' => [
                'dry_season' => 'Dry season',
                'high_temperature' => 'High temperature',
                'low_humidity' => 'Low humidity',
                'strong_winds' => 'Strong winds that may accelerate fire spread',
            ],
            'occupancy_hazard' => [
                'buildings_used_beyond_capacity' => 'Buildings used beyond intended capacity',
                'mixed_residential_commercial_use' => 'Mixed residential-commercial use',
                'absence_of_evacuation_plans' => 'Absence of evacuation plans',
                'lack_of_safety_orientation' => 'Lack of safety orientation',
            ],
            'fire_protection_deficiency_hazard' => [
                'expired_fire_extinguishers' => 'Expired fire extinguishers',
                'absence_of_smoke_detectors' => 'Absence of smoke detectors',
                'missing_fire_alarms' => 'Missing fire alarms',
                'no_evacuation_signage' => 'No evacuation signage',
                'lack_of_emergency_lighting' => 'Lack of emergency lighting',
            ],
        ];
    }

    protected function allConditionCodes(): array
    {
        return collect($this->riskConditionOptions())
            ->merge($this->hazardConditionOptions())
            ->flatMap(fn (array $options) => array_keys($options))
            ->unique()
            ->values()
            ->all();
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
        if ($entryType === 'risk' && in_array($conditionCode, $this->allConditionCodes(), true)) {
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

    protected function resolveBarangayContext(): array
    {
        $user = Auth::user()->loadMissing([
            'role',
            'address.barangay.city',
            'barangayOfficer.barangay.city',
            'barangayOfficer.captainHall.marker',
            'barangayOfficer.captainHall.barangay.city',
            'barangayOfficer.captainHall.address.barangay.city',
            'captainedBarangayHall.marker',
            'captainedBarangayHall.barangay.city',
            'captainedBarangayHall.address.barangay.city',
        ]);

        $officer = $user->barangayOfficer;
        $hall = $officer?->captainHall ?? $user->captainedBarangayHall;
        $barangay = $hall?->barangay ?? $officer?->barangay ?? $user->address?->barangay;

        if (! $hall && $barangay) {
            $hall = BarangayHall::query()
                ->with(['barangay.city', 'address.barangay.city'])
                ->where('barangay_id', $barangay->barangay_id)
                ->first();
        }

        return [
            'user' => $user,
            'hall' => $hall,
            'barangay' => $barangay,
            'city' => $barangay?->city,
        ];
    }

    protected function buildPortalData(bool $loadAllChecklistRecords = false, bool $loadFireRecords = true): array
    {
        $context = $this->resolveBarangayContext();
        /** @var Barangay|null $barangay */
        $barangay = $context['barangay'];
        /** @var BarangayHall|null $hall */
        $hall = $context['hall'];

        $historicalIncidents = collect();
        $predictionChecklists = collect();
        $aiActionPlanChecklists = collect();
        $communityActivityRequests = collect();
        $fireRecords = collect();
        $riskAreas = collect();
        $mitigationActions = collect();
        $markers = collect();
        $miniMapPins = collect();
        $historicalStats = [
            'total' => 0,
            'incident_count' => 0,
            'recent_30_days' => 0,
            'previous_30_days' => 0,
        ];
        $checklistStats = [
            'total' => 0,
            'completed' => 0,
            'open' => 0,
            'critical' => 0,
        ];
        $riskLevelCounts = ['high' => 0, 'medium' => 0, 'low' => 0];
        $openMitigationCount = 0;
        $stations = Station::query()->orderBy('station_name')->get();

        if ($barangay) {
            $historicalIncidents = HistoricalFireIncident::query()
                ->select([
                    'historical_fire_incident_id',
                    'barangay_id',
                    'incident_id',
                    'incident_date',
                    'incident_count',
                    'fire_classification',
                    'fire_cause',
                    'incident_location',
                    'is_verified',
                ])
                ->with(['incident.marker:marker_id,title,category,lat,lng'])
                ->where('barangay_id', $barangay->barangay_id)
                ->orderByDesc('incident_date')
                ->limit(12)
                ->get();

            $historicalBase = HistoricalFireIncident::query()
                ->where('barangay_id', $barangay->barangay_id);
            $historicalStats = [
                'total' => (clone $historicalBase)->count(),
                'incident_count' => (int) (clone $historicalBase)->sum('incident_count'),
                'recent_30_days' => (clone $historicalBase)->whereDate('incident_date', '>=', now()->subDays(30))->count(),
                'previous_30_days' => (clone $historicalBase)
                    ->whereDate('incident_date', '>=', now()->subDays(60))
                    ->whereDate('incident_date', '<', now()->subDays(30))
                    ->count(),
            ];

            $predictionChecklists = BarangayPredictionChecklist::query()
                ->select([
                    'barangay_prediction_checklist_id',
                    'barangay_id',
                    'analysis_id',
                    'title',
                    'checklist_type',
                    'ai_output',
                    'planning_notes',
                    'priority_level',
                    'status',
                    'target_date',
                    'assigned_to',
                    'reviewed_by',
                    'compliance_remarks',
                    'is_ai_generated',
                ])
                ->with([
                    'analysis:analysis_id,sector,period,risk_level,confidence_data,analysis_data,predictions,incident_count,probability,revision_number,created_at',
                    'assignee:user_id,first_name,middle_name,last_name',
                    'reviewer:user_id,first_name,middle_name,last_name',
                ])
                ->where('barangay_id', $barangay->barangay_id)
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderBy('target_date')
                ->when(! $loadAllChecklistRecords, fn ($query) => $query->limit(12))
                ->get();

            $predictionBase = BarangayPredictionChecklist::query()
                ->where('barangay_id', $barangay->barangay_id);
            $checklistStats = [
                'total' => (clone $predictionBase)->count(),
                'completed' => (clone $predictionBase)->where('status', 'complied')->count(),
                'open' => (clone $predictionBase)->whereIn('status', ['pending', 'in_progress', 'not_complied'])->count(),
                'critical' => (clone $predictionBase)->whereIn('priority_level', ['critical', 'high'])->count(),
            ];

            $aiActionPlanChecklists = AiActionPlanChecklist::query()
                ->select([
                    'ai_action_plan_checklist_id',
                    'barangay_id',
                    'analysis_id',
                    'source_type',
                    'source_title',
                    'source_description',
                    'plan_title',
                    'plan_description',
                    'success_indicator',
                    'priority_level',
                    'status',
                    'target_date',
                    'assigned_to',
                    'confirmed_by',
                    'confirmed_at',
                    'confirmation_notes',
                ])
                ->with([
                    'analysis:analysis_id,sector,period,risk_level,confidence_data,analysis_data,predictions,incident_count,probability,revision_number,created_at',
                    'assignee:user_id,first_name,middle_name,last_name',
                    'confirmer:user_id,first_name,middle_name,last_name',
                    'items:ai_action_plan_checklist_item_id,ai_action_plan_checklist_id,item_order,item_text,status',
                ])
                ->where('barangay_id', $barangay->barangay_id)
                ->whereIn('status', ['confirmed', 'in_progress', 'submitted'])
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderBy('target_date')
                ->when(! $loadAllChecklistRecords, fn ($query) => $query->limit(12))
                ->get();

            $markers = Marker::query()
                ->select(['marker_id', 'title', 'description', 'lat', 'lng', 'category', 'status'])
                ->whereIn('category', ['barangay', 'fire', 'school', 'hydrant', 'bridge', 'station', 'risk', 'hazard'])
                ->orderByDesc('marker_id')
                ->limit(18)
                ->get();

            $communityActivityRequests = CommunityActivityRequest::isAvailable()
                ? CommunityActivityRequest::query()
                    ->with(['station:station_id,station_name', 'convertedEvent:event_id,event,event_date,schedule'])
                    ->where('barangay_id', $barangay->barangay_id)
                    ->latest()
                    ->limit(6)
                    ->get()
                : collect();

        }

        if ($hall) {
            if ($loadFireRecords) {
                $fireRecords = BarangayFireRecord::query()
                    ->with(['incident.marker:marker_id,title,category,lat,lng'])
                    ->where('barangay_hall_id', $hall->barangay_hall_id)
                    ->orderByDesc('incident_date')
                    ->get();
            }

            $riskAreas = RiskArea::query()
                ->select([
                    'risk_area_id',
                    'marker_id',
                    'station_id',
                    'barangay_hall_id',
                    'entry_type',
                    'risk_type',
                    'hazard_type',
                    'risk_factor',
                    'condition_label',
                    'status',
                    'reported_by',
                    'created_at',
                ])
                ->with([
                    'marker:marker_id,title,category,lat,lng',
                    'station:station_id,station_name',
                    'reporter:user_id,first_name,middle_name,last_name',
                    'photos:risk_area_photo_id,risk_area_id,photo_path,photo_name',
                ])
                ->where('barangay_hall_id', $hall->barangay_hall_id)
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();

            $riskLevelCounts = RiskArea::query()
                ->where('barangay_hall_id', $hall->barangay_hall_id)
                ->where(function ($query) {
                    $query->whereNull('review_status')->orWhere('review_status', '!=', 'rejected');
                })
                ->where(function ($query) {
                    $query->where('review_type', '!=', 'delete')
                        ->orWhereNull('review_type')
                        ->orWhere('review_status', '!=', 'approved');
                })
                ->get(['assessment_level', 'risk_score'])
                ->map(function (RiskArea $riskArea) {
                    $assessment = strtolower(trim((string) ($riskArea->assessment_level ?? '')));
                    $score = (float) ($riskArea->risk_score ?? 0);

                    if ($assessment === 'high' || $score >= 70) return 'high';
                    if (in_array($assessment, ['moderate', 'medium'], true) || $score >= 40) return 'medium';
                    return 'low';
                })
                ->countBy()
                ->pipe(fn (Collection $counts) => [
                    'high' => (int) $counts->get('high', 0),
                    'medium' => (int) $counts->get('medium', 0),
                    'low' => (int) $counts->get('low', 0),
                ]);

            $mitigationActions = MitigationAction::query()
                ->select([
                    'mitigation_action_id',
                    'barangay_id',
                    'risk_area_id',
                    'barangay_prediction_checklist_id',
                    'ai_action_plan_checklist_id',
                    'title',
                    'priority_level',
                    'status',
                    'target_date',
                    'description',
                    'created_by',
                    'created_at',
                ])
                ->with([
                    'riskArea:risk_area_id,marker_id',
                    'riskArea.marker:marker_id,title',
                    'checklist:barangay_prediction_checklist_id,title',
                    'aiActionPlanChecklist:ai_action_plan_checklist_id,plan_title',
                    'creator:user_id,first_name,middle_name,last_name',
                    'attachments:mitigation_action_attachment_id,mitigation_action_id,file_path,file_name',
                ])
                ->where('barangay_id', $barangay?->barangay_id)
                ->orderByRaw("
                    CASE priority_level
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                        ELSE 5
                    END
                ")
                ->orderByRaw("
                    CASE status
                        WHEN 'in_progress' THEN 1
                        WHEN 'planned' THEN 2
                        WHEN 'blocked' THEN 3
                        WHEN 'completed' THEN 4
                        WHEN 'cancelled' THEN 5
                        ELSE 6
                    END
                ")
                ->orderBy('target_date')
                ->orderByDesc('created_at')
                ->when(! $loadAllChecklistRecords, fn ($query) => $query->limit(16))
                ->get();

            $openMitigationCount = MitigationAction::query()
                ->where('barangay_id', $barangay?->barangay_id)
                ->whereIn('status', ['planned', 'in_progress', 'blocked'])
                ->count();

            if ($hall->marker && $hall->marker->lat !== null && $hall->marker->lng !== null) {
                $miniMapPins->push([
                    'title' => $hall->hall_name ?: 'Barangay Hall',
                    'category' => 'barangay',
                    'lat' => (float) $hall->marker->lat,
                    'lng' => (float) $hall->marker->lng,
                    'meta' => $barangay?->barangay_name ?: 'Barangay hall',
                ]);
            }
        }

        $latestPosts = Post::query()
            ->with('user')
            ->latest()
            ->limit(6)
            ->get();

        $hazardMix = $this->groupCounts(
            $historicalIncidents,
            'fire_classification',
            ['Structural', 'Electrical', 'Grass', 'Vehicular', 'Other']
        );

        $statusMix = $this->groupCounts(
            $predictionChecklists,
            'status',
            ['pending', 'in_progress', 'complied', 'not_complied', 'deferred']
        );

        $priorityMix = $this->groupCounts(
            $predictionChecklists,
            'priority_level',
            ['critical', 'high', 'medium', 'low']
        );

        $miniMapPins = $miniMapPins
            ->merge(
                $historicalIncidents
                    ->map(function (HistoricalFireIncident $incident) {
                        $marker = $incident->incident?->marker;

                        if (! $marker || $marker->lat === null || $marker->lng === null) {
                            return null;
                        }

                        return [
                            'title' => $marker->title ?: ($incident->incident_location ?: 'Hazard pin'),
                            'category' => $marker->category ?: 'fire',
                            'lat' => (float) $marker->lat,
                            'lng' => (float) $marker->lng,
                            'meta' => optional($incident->incident_date)->format('M d, Y') ?: 'Recent pin',
                        ];
                    })
                    ->filter()
            )
            ->merge(
                $riskAreas
                    ->map(function (RiskArea $riskArea) {
                        $marker = $riskArea->marker;

                        if (! $marker || $marker->lat === null || $marker->lng === null) {
                            return null;
                        }

                        return [
                            'title' => $marker->title ?: 'Risk marker',
                            'category' => $riskArea->entry_type === 'hazard' ? 'hazard' : 'risk',
                            'lat' => (float) $marker->lat,
                            'lng' => (float) $marker->lng,
                            'meta' => strtoupper($riskArea->status ?: 'pending') . ' · ' . ($riskArea->condition_label ?: str_replace('_', ' ', $riskArea->risk_type ?: $riskArea->hazard_type ?: $riskArea->risk_factor)),
                        ];
                    })
                    ->filter()
            )
            ->unique(function (array $pin) {
                return $pin['title'] . '|' . $pin['lat'] . '|' . $pin['lng'];
            })
            ->take(6)
            ->values();

        $barangayBoundaryCollection = BoundaryLocation::query()
            ->select(['id', 'barangay_id', 'geojson', 'updated_at'])
            ->with('barangay:barangay_id,barangay_name')
            ->where('boundary_type', 'barangay')
            ->whereNotNull('geojson')
            ->orderByDesc('updated_at')
            ->get()
            ->unique('barangay_id')
            ->values()
            ->map(function (BoundaryLocation $boundaryLocation) {
                return [
                    'boundary_location_id' => $boundaryLocation->id,
                    'barangay_id' => $boundaryLocation->barangay_id,
                    'barangay_name' => $boundaryLocation->barangay?->barangay_name,
                    'geojson' => $boundaryLocation->geojson,
                    'updated_at' => optional($boundaryLocation->updated_at)->toIso8601String(),
                ];
            })
            ->values();

        return array_merge($context, [
            'historicalIncidents' => $historicalIncidents,
            'predictionChecklists' => $predictionChecklists,
            'aiActionPlanChecklists' => $aiActionPlanChecklists,
            'communityActivityRequests' => $communityActivityRequests,
            'fireRecords' => $fireRecords,
            'riskAreas' => $riskAreas,
            'mitigationActions' => $mitigationActions,
            'markers' => $markers,
            'miniMapPins' => $miniMapPins,
            'barangayBoundaryCollection' => $barangayBoundaryCollection,
            'currentBarangayId' => $barangay?->barangay_id,
            'latestPosts' => $latestPosts,
            'stations' => $stations,
            'hazardMix' => $hazardMix,
            'statusMix' => $statusMix,
            'priorityMix' => $priorityMix,
            'riskAreaCounts' => $riskLevelCounts,
            'portalStats' => [
                'historical_total' => $historicalStats['total'],
                'recent_incidents' => $historicalStats['recent_30_days'],
                'previous_incidents' => $historicalStats['previous_30_days'],
                'checklist_total' => $checklistStats['total'],
                'checklist_completed' => $checklistStats['completed'],
                'open_actions' => $checklistStats['open'],
                'critical_actions' => $checklistStats['critical'] + $aiActionPlanChecklists->whereIn('priority_level', ['critical', 'high'])->count(),
                'mitigation_open' => $openMitigationCount,
                'recorded_reports' => $fireRecords->count(),
                'risk_markers' => $riskAreas->count(),
                'total_hazards_reported' => max($historicalStats['total'], $historicalStats['incident_count']),
                'pending_inspections' => $checklistStats['open']
                    + $aiActionPlanChecklists->whereIn('status', ['pending', 'in_progress', 'submitted'])->count(),
            ],
            'riskTypeOptions' => $this->riskTypeOptions(),
            'riskTypeGroups' => $this->riskTypeGroups(),
            'hazardTypeOptions' => $this->hazardTypeOptions(),
            'riskConditionOptions' => $this->riskConditionOptions(),
            'hazardConditionOptions' => $this->hazardConditionOptions(),
        ]);
    }

    protected function groupCounts(Collection $items, string $field, array $labels): array
    {
        return collect($labels)->mapWithKeys(function (string $label) use ($items, $field) {
            return [$label => $items->where($field, $label)->count()];
        })->all();
    }

    protected function riskAreaValidationRules(): array
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
            'estimated_radius' => ['nullable', 'numeric', 'between:0,10000'],
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
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:' . self::RISK_HAZARD_REPORTS_MAX_FILES],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp'],
        ];
    }

    protected function validateRiskHazardPhotoUpload(Request $request): void
    {
        $photos = collect($request->file('photos', []))
            ->filter()
            ->values();

        if ($photos->isEmpty()) {
            return;
        }

        if ($photos->count() > self::RISK_HAZARD_REPORTS_MAX_FILES) {
            throw ValidationException::withMessages([
                'photos' => 'You may upload up to 3 photos only.',
            ]);
        }

        $totalBytes = $photos->sum(fn ($photo) => (int) $photo->getSize());

        if ($totalBytes > self::RISK_HAZARD_REPORTS_MAX_TOTAL_BYTES) {
            throw ValidationException::withMessages([
                'photos' => 'The selected photos exceed the 5 MB total upload limit.',
            ]);
        }
    }

    protected function buildRiskAreaStructuredNotes(array $validated): string
    {
        return collect([
            !empty($validated['entry_type']) ? 'Entry Type: ' . ucfirst($validated['entry_type']) : null,
            !empty($validated['street_or_sitio']) ? 'Street/Sitio: ' . $validated['street_or_sitio'] : null,
            !empty($validated['risk_type']) ? 'Risk Type: ' . str_replace('_', ' ', $validated['risk_type']) : null,
            !empty($validated['hazard_category']) ? 'Hazard Type: ' . ($this->hazardTypeOptions()[$validated['hazard_category']] ?? str_replace('_', ' ', $validated['hazard_category'])) : null,
            !empty($validated['condition_code']) ? 'Condition: ' . ($this->resolveConditionLabel(
                $validated['entry_type'],
                $validated['risk_type'] ?? null,
                $validated['hazard_category'] ?? null,
                $validated['condition_code']
            ) ?? str_replace('_', ' ', $validated['condition_code'])) : null,
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

    protected function resolveOwnedRiskArea(int $riskAreaId): RiskArea
    {
        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        if (! $hall) {
            throw (new ModelNotFoundException())->setModel(RiskArea::class, [$riskAreaId]);
        }

        return RiskArea::query()
            ->with(['marker', 'photos'])
            ->where('barangay_hall_id', $hall->barangay_hall_id)
            ->findOrFail($riskAreaId);
    }

    protected function serializeRiskAreaForBarangayTable(RiskArea $riskArea): array
    {
        $riskArea->loadMissing(['marker', 'photos']);

        $entryType = $riskArea->entry_type ?? 'risk';
        $typeLabel = $entryType === 'hazard'
            ? ($this->hazardTypeOptions()[$riskArea->hazard_category ?? ''] ?? ucwords(str_replace('_', ' ', $riskArea->hazard_type ?: 'general')))
            : ($this->riskTypeOptions()[$riskArea->risk_type ?? ''] ?? ucwords(str_replace('_', ' ', $riskArea->risk_type ?: 'general')));

        $statusLabel = strtoupper($riskArea->status ?? 'pending');
        $reviewLabel = strtoupper(($riskArea->review_type ?? 'create') . ' / ' . ($riskArea->review_status ?? 'pending'));
        $locationLabel = $riskArea->street_or_sitio ?: 'General barangay area';

        return [
            'id' => $riskArea->risk_area_id,
            'entry_type' => $entryType,
            'date' => optional($riskArea->created_at)->format('M d, Y'),
            'title' => $riskArea->marker?->title ?? 'Untitled entry',
            'type_label' => $typeLabel,
            'status_label' => $statusLabel,
            'review_label' => $reviewLabel,
            'location_label' => $locationLabel,
            'search' => strtolower(trim(($riskArea->marker?->title ?? 'Untitled entry') . ' ' . $typeLabel . ' ' . $statusLabel . ' ' . $reviewLabel . ' ' . $locationLabel)),
            'edit_payload' => [
                'id' => $riskArea->risk_area_id,
                'title' => $riskArea->marker?->title,
                'description' => $riskArea->marker?->description,
                'lat' => $riskArea->marker?->lat,
                'lng' => $riskArea->marker?->lng,
                'entry_type' => $entryType,
                'street_or_sitio' => $riskArea->street_or_sitio,
                'station_id' => $riskArea->station_id,
                'risk_type' => $riskArea->risk_type,
                'hazard_category' => $riskArea->hazard_category,
                'hazard_type' => $riskArea->hazard_type,
                'condition_code' => $riskArea->condition_code ?? $riskArea->mode,
                'condition_label' => $riskArea->condition_label,
                'notes' => $riskArea->remarks,
                'population_density_level' => $riskArea->population_density_level,
                'building_density_level' => $riskArea->building_density_level,
                'road_accessibility' => $riskArea->road_accessibility,
                'assessment_level' => $riskArea->assessment_level,
                'distance_to_hydrant' => $riskArea->distance_to_hydrant,
                'distance_to_water_source' => $riskArea->distance_to_water_source,
                'estimated_affected_households' => $riskArea->estimated_affected_households,
                'risk_score' => $riskArea->risk_score,
                'nearby_establishments' => $riskArea->nearby_establishments,
                'assessment_description' => $riskArea->assessment_description,
                'remarks' => $riskArea->remarks,
                'status' => $riskArea->status,
                'review_status' => $riskArea->review_status ?? 'pending',
                'review_type' => $riskArea->review_type ?? 'create',
                'near_hydrant' => (bool) $riskArea->near_hydrant,
                'near_water_source' => (bool) $riskArea->near_water_source,
                'blocked_hydrant' => (bool) $riskArea->blocked_hydrant,
                'informal_settlement' => (bool) $riskArea->informal_settlement,
                'cooking_fire_prone_activity' => (bool) $riskArea->cooking_fire_prone_activity,
                'combustible_materials_present' => (bool) $riskArea->combustible_materials_present,
                'poor_waste_disposal' => (bool) $riskArea->poor_waste_disposal,
                'previous_fire_prone_location' => (bool) $riskArea->previous_fire_prone_location,
                'report_kind' => $entryType,
            ],
            'photos' => $riskArea->photos->take(3)->map(function (RiskAreaPhoto $photo) {
                return [
                    'url' => MediaPath::url($photo->photo_path),
                    'name' => $photo->photo_name ?: 'Risk area photo',
                ];
            })->values()->all(),
            'delete_pending' => ($riskArea->review_type ?? 'create') === 'delete' && ($riskArea->review_status ?? 'pending') === 'pending',
        ];
    }

    public function dashboard(Request $request)
    {
        $data = $this->buildPortalData();
        $data['dashboardRecords'] = $this->paginateDashboardRecords(
            $data['barangay'] ?? null,
            $request
        );

        return view('barangay.dashboard', $data);
    }

    public function dashboardRecords(Request $request)
    {
        $context = $this->resolveBarangayContext();

        return response()->json([
            'success' => true,
            'records' => $this->paginateDashboardRecords($context['barangay'] ?? null, $request),
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    protected function paginateDashboardRecords(?Barangay $barangay, Request $request)
    {
        $perPage = min(10, max(4, (int) $request->integer('per_page', 6)));

        $query = HistoricalFireIncident::query()
            ->select([
                'historical_fire_incident_id',
                'barangay_id',
                'incident_id',
                'incident_date',
                'incident_time',
                'incident_count',
                'fire_classification',
                'fire_cause',
                'incident_location',
                'source_name',
                'source_type',
                'injured_count',
                'death_count',
                'estimated_damage',
                'is_verified',
                'notes',
            ])
            ->with([
                'incident:incident_id,marker_id,status,created_at,updated_at',
                'incident.marker:marker_id,title,description,category,lat,lng,status',
            ])
            ->when(
                $barangay,
                fn ($builder) => $builder->where('barangay_id', $barangay->barangay_id),
                fn ($builder) => $builder->whereRaw('1 = 0')
            )
            ->orderByDesc('incident_date')
            ->orderByDesc('incident_time')
            ->orderByDesc('historical_fire_incident_id');

        return $query
            ->paginate($perPage, ['*'], 'records_page')
            ->withQueryString()
            ->through(fn (HistoricalFireIncident $record) => $this->serializeDashboardRecord($record));
    }

    protected function serializeDashboardRecord(HistoricalFireIncident $record): array
    {
        $incidentStatus = trim((string) ($record->incident?->status ?? ''));
        $displayStatus = $incidentStatus !== ''
            ? $incidentStatus
            : ($record->is_verified ? 'Verified' : 'Pending review');

        $date = $record->incident_date?->format('M d, Y');
        $time = $record->incident_time
            ? date('h:i A', strtotime((string) $record->incident_time))
            : null;

        return [
            'id' => (int) $record->historical_fire_incident_id,
            'incident_id' => $record->incident_id ? (int) $record->incident_id : null,
            'date' => $date ?: 'Date not recorded',
            'time' => $time ?: 'Time not recorded',
            'classification' => ucfirst(trim((string) ($record->fire_classification ?: 'Undetermined'))),
            'cause' => trim((string) ($record->fire_cause ?: 'Pending investigation')),
            'location' => trim((string) ($record->incident_location ?: $record->incident?->marker?->title ?: 'Location not recorded')),
            'status' => $displayStatus,
            'status_key' => strtolower(str_replace([' ', '_'], '-', $displayStatus)),
            'verified' => (bool) $record->is_verified,
            'incident_count' => max(1, (int) ($record->incident_count ?? 1)),
            'injured_count' => max(0, (int) ($record->injured_count ?? 0)),
            'death_count' => max(0, (int) ($record->death_count ?? 0)),
            'estimated_damage' => $record->estimated_damage !== null
                ? 'PHP ' . number_format((float) $record->estimated_damage, 2)
                : 'Not recorded',
            'source' => trim((string) ($record->source_name ?: $record->source_type ?: 'System record')),
            'notes' => trim((string) ($record->notes ?: 'No additional notes.')),
            'marker' => $record->incident?->marker ? [
                'title' => $record->incident->marker->title,
                'description' => $record->incident->marker->description,
                'lat' => $record->incident->marker->lat,
                'lng' => $record->incident->marker->lng,
            ] : null,
        ];
    }

    public function hazards()
    {
        return view('barangay.hazards', $this->buildPortalData());
    }

    public function riskMap(Request $request)
    {
        $data = $this->buildPortalData();
        /** @var BarangayHall|null $hall */
        $hall = $data['hall'] ?? null;

        $riskAreaFeed = RiskArea::query()
            ->select([
                'risk_area_id',
                'marker_id',
                'station_id',
                'barangay_hall_id',
                'risk_type',
                'risk_factor',
                'hazard_type',
                'assessment_level',
                'risk_score',
                'street_or_sitio',
                'remarks',
                'status',
                'created_at',
            ])
            ->with([
                'marker:marker_id,title,description,category,lat,lng',
                'station:station_id,station_name',
                'photos:risk_area_photo_id,risk_area_id,photo_path,photo_name',
            ])
            ->when(
                $hall,
                fn ($query) => $query->where('barangay_hall_id', $hall->barangay_hall_id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest('created_at')
            ->paginate(5, ['*'], 'risk_page')
            ->withQueryString();

        $markerInventory = Marker::query()
            ->select(['marker_id', 'title', 'description', 'lat', 'lng', 'category', 'status'])
            ->whereIn('category', ['barangay', 'fire', 'school', 'hydrant', 'bridge', 'station', 'risk', 'hazard'])
            ->latest('marker_id')
            ->paginate(6, ['*'], 'marker_page')
            ->withQueryString();

        $riskAreaStatusCounts = RiskArea::query()
            ->when(
                $hall,
                fn ($query) => $query->where('barangay_hall_id', $hall->barangay_hall_id),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->selectRaw('LOWER(status) AS normalized_status, COUNT(*) AS aggregate')
            ->groupByRaw('LOWER(status)')
            ->pluck('aggregate', 'normalized_status');

        $barangayBoundary = ($data['barangay'] ?? null)
            ? BoundaryLocation::query()
                ->where('barangay_id', $data['barangay']->barangay_id)
                ->where('boundary_type', 'barangay')
                ->whereNotNull('geojson')
                ->latest('updated_at')
                ->first()
            : null;

        $data['riskAreaFeed'] = $riskAreaFeed;
        $data['markerInventory'] = $markerInventory;
        $data['riskAreaStatusCounts'] = $riskAreaStatusCounts;
        $data['barangayBoundaryGeojson'] = $barangayBoundary?->geojson;
        $data['portalStats']['risk_markers'] = $riskAreaFeed->total();

        return view('barangay.risk-map', $data);
    }

    public function patrols()
    {
        return view('barangay.patrols', $this->buildPortalData());
    }

    public function assessment()
    {
        return view('barangay.assessment', $this->buildPortalData());
    }

    public function checklists()
    {
        return view('barangay.checklists', $this->buildPortalData(true));
    }

    public function checklistsContent()
    {
        return view('barangay.partials.checklists-content', $this->buildPortalData(true));
    }

    public function reports(Request $request)
    {
        $data = $this->buildPortalData(false, false);
        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];
        $barangay = $context['barangay'];
        $filters = $request->only(['search', 'classification', 'date_from', 'date_to']);
        $data['fireRecords'] = $this->paginateFireReportRecords($hall, $barangay, $request);
        $data['fireRecordFilters'] = $filters;
        $data['fireRecordTotalCount'] = BarangayFireRecord::query()
            ->join('barangay_halls', 'barangay_fire_records.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->when(
                $barangay,
                fn ($builder) => $builder->where('barangay_halls.barangay_id', $barangay->barangay_id),
                fn ($builder) => $builder->whereRaw('1 = 0')
            )
            ->count();

        return view('barangay.reports', $data);
    }

    public function reportRecords(Request $request)
    {
        $context = $this->resolveBarangayContext();

        return response()->json([
            'success' => true,
            'records' => $this->paginateFireReportRecords($context['hall'] ?? null, $context['barangay'] ?? null, $request),
        ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
    }

    protected function paginateFireReportRecords(?BarangayHall $hall, ?Barangay $barangay, Request $request)
    {
        $perPage = min(12, max(5, (int) $request->integer('per_page', 8)));
        $query = BarangayFireRecord::query()
            ->select([
                'barangay_fire_records.barangay_fire_record_id',
                'barangay_fire_records.barangay_hall_id',
                'barangay_fire_records.incident_id',
                'barangay_fire_records.incident_date',
                'barangay_fire_records.incident_count',
                'barangay_fire_records.fire_classification',
                'barangay_fire_records.fire_cause',
                'barangay_fire_records.notes',
                'barangay_fire_records.created_at',
            ])
            ->join('barangay_halls', 'barangay_fire_records.barangay_hall_id', '=', 'barangay_halls.barangay_hall_id')
            ->with(['incident:incident_id,marker_id,status', 'incident.marker:marker_id,title,description,lat,lng,category,status'])
            ->when(
                $barangay,
                fn ($builder) => $builder->where('barangay_halls.barangay_id', $barangay->barangay_id),
                fn ($builder) => $builder->whereRaw('1 = 0')
            )
            ->when($hall, fn ($builder) => $builder->where('barangay_fire_records.barangay_hall_id', $hall->barangay_hall_id));

        $classification = strtolower(trim((string) $request->input('classification', '')));
        if (in_array($classification, ['accidental', 'intentional', 'undetermined'], true)) {
            $query->where('barangay_fire_records.fire_classification', $classification);
        }
        if ($request->filled('date_from')) $query->whereDate('barangay_fire_records.incident_date', '>=', $request->input('date_from'));
        if ($request->filled('date_to')) $query->whereDate('barangay_fire_records.incident_date', '<=', $request->input('date_to'));

        $search = strtolower(trim((string) $request->input('search', '')));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($builder) use ($like) {
                $builder->whereRaw('LOWER(COALESCE(barangay_fire_records.fire_cause, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(barangay_fire_records.notes, \'\')) LIKE ?', [$like])
                    ->orWhereHas('incident.marker', function ($markerQuery) use ($like) {
                        $markerQuery->whereRaw('LOWER(COALESCE(title, \'\')) LIKE ?', [$like])
                            ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$like]);
                    });
            });
        }

        return $query->orderByDesc('barangay_fire_records.incident_date')
            ->orderByDesc('barangay_fire_records.barangay_fire_record_id')
            ->paginate($perPage, ['*'], 'reports_page')
            ->withQueryString()
            ->through(fn (BarangayFireRecord $record) => $this->serializeFireReportRecord($record));
    }

    protected function serializeFireReportRecord(BarangayFireRecord $record): array
    {
        $marker = $record->incident?->marker;

        return [
            'id' => (int) $record->barangay_fire_record_id,
            'incident_id' => $record->incident_id ? (int) $record->incident_id : null,
            'date' => $record->incident_date?->format('M d, Y') ?: 'No date',
            'incident_date' => $record->incident_date?->toDateString() ?: now()->toDateString(),
            'title' => $marker?->title ?: 'Barangay Fire Record',
            'classification' => ucfirst($record->fire_classification ?: 'undetermined'),
            'classification_key' => strtolower($record->fire_classification ?: 'undetermined'),
            'cause' => $record->fire_cause ?: 'Pending assessment',
            'location' => $marker?->description ?: 'No location description',
            'incident_count' => max(1, (int) ($record->incident_count ?? 1)),
            'status' => $record->incident?->status ?: 'Reported',
            'lat' => $marker?->lat,
            'lng' => $marker?->lng,
            'notes' => $record->notes ?: '',
        ];
    }

    protected function fireReportValidationRules(bool $isUpdate = false): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'location_description' => ['required', 'string', 'max:1000'],
            'fire_classification' => ['required', Rule::in(['accidental', 'intentional', 'undetermined'])],
            'fire_cause' => ['nullable', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'incident_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function storeFireReport(Request $request)
    {
        $validated = $request->validate($this->fireReportValidationRules());

        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        DB::transaction(function () use ($validated, $hall, $request) {
            $marker = Marker::create([
                'title' => $validated['title'],
                'description' => $validated['location_description'],
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'active',
                'category' => 'fire',
            ]);

            $incident = Incident::create([
                'marker_id' => $marker->marker_id,
                'status' => 'Reported',
                'team_id' => null,
                'reported_by' => Auth::user()?->user_id,
            ]);
            IncidentContextDispatcher::dispatchFor($incident);

            if ($hall) {
                BarangayFireRecord::create([
                    'barangay_hall_id' => $hall->barangay_hall_id,
                    'incident_id' => $incident->incident_id,
                    'reported_by' => Auth::user()?->user_id,
                    'incident_date' => $validated['incident_date'],
                    'incident_count' => $validated['incident_count'] ?? 1,
                    'fire_classification' => $validated['fire_classification'],
                    'fire_cause' => $validated['fire_cause'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            UserActivityLogger::log('submitted fire report', Auth::user()?->user_id, $request);
        });

        return redirect()
            ->route('barangay.reports')
            ->with('success', 'Fire report submitted successfully and forwarded into the system incident flow.');
    }

    public function updateFireReport(Request $request, string $recordId)
    {
        $validated = $request->validate($this->fireReportValidationRules(true));

        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        if (! $hall) {
            return redirect()
                ->route('barangay.reports')
                ->withErrors(['fire_record' => 'Your barangay account is not linked to a barangay hall yet.']);
        }

        $record = BarangayFireRecord::with(['incident.marker'])
            ->where('barangay_hall_id', $hall->barangay_hall_id)
            ->findOrFail($recordId);

        DB::transaction(function () use ($record, $validated, $request) {
            $record->incident?->marker?->update([
                'title' => $validated['title'],
                'description' => $validated['location_description'],
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
            ]);

            $record->update([
                'incident_date' => $validated['incident_date'],
                'incident_count' => $validated['incident_count'] ?? 1,
                'fire_classification' => $validated['fire_classification'],
                'fire_cause' => $validated['fire_cause'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            HistoricalFireIncidentSynchronizer::syncForIncidentId((int) $record->incident_id);
            UserActivityLogger::log('updated fire report record', Auth::user()?->user_id, $request);
        });

        return redirect()
            ->route('barangay.reports')
            ->with('success', 'Fire record updated successfully.');
    }

    public function destroyFireReport(Request $request, string $recordId)
    {
        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        if (! $hall) {
            return redirect()
                ->route('barangay.reports')
                ->withErrors(['fire_record' => 'Your barangay account is not linked to a barangay hall yet.']);
        }

        $record = BarangayFireRecord::with(['incident.marker'])
            ->where('barangay_hall_id', $hall->barangay_hall_id)
            ->findOrFail($recordId);

        DB::transaction(function () use ($record, $request) {
            $incident = $record->incident;
            $marker = $incident?->marker;
            $incidentId = $record->incident_id;

            $record->delete();

            HistoricalFireIncident::query()
                ->where('incident_id', $incidentId)
                ->delete();

            if ($incident) {
                IncidentContext::query()
                    ->where('incident_id', $incident->incident_id)
                    ->delete();
                $incident->delete();
            }

            if ($marker) {
                $marker->delete();
            }

            UserActivityLogger::log('deleted fire report record', Auth::user()?->user_id, $request);
        });

        return redirect()
            ->route('barangay.reports')
            ->with('success', 'Fire record deleted successfully.');
    }

    public function storeQuickFireReport(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];
        $userId = Auth::user()?->user_id;
        $timestamp = now();

        $incident = DB::transaction(function () use ($validated, $hall, $userId, $timestamp, $request) {
            $marker = Marker::create([
                'title' => 'Barangay Fire Emergency',
                'description' => 'Fire emergency reported from barangay quick action.',
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'active',
                'category' => 'fire',
            ]);

            $incident = Incident::create([
                'marker_id' => $marker->marker_id,
                'status' => 'Reported',
                'team_id' => null,
                'reported_by' => $userId,
            ]);
            IncidentContextDispatcher::dispatchFor($incident);

            if ($hall) {
                BarangayFireRecord::create([
                    'barangay_hall_id' => $hall->barangay_hall_id,
                    'incident_id' => $incident->incident_id,
                    'reported_by' => $userId,
                    'incident_date' => $timestamp->toDateString(),
                    'incident_count' => 1,
                    'fire_classification' => 'undetermined',
                    'fire_cause' => null,
                    'notes' => 'Submitted from the barangay quick report action.',
                ]);
            }

            UserActivityLogger::log('submitted quick fire report', $userId, $request);

            return $incident;
        });

        return response()->json([
            'success' => true,
            'message' => 'Fire report submitted successfully.',
            'data' => [
                'incident_id' => $incident->incident_id,
            ],
        ]);
    }

    public function storeRiskArea(Request $request)
    {
        $returnRoute = $request->input('return_to') === 'hazards'
            ? 'barangay.hazards'
            : 'barangay.risk-map';

        $validated = $request->validate($this->riskAreaValidationRules());
        $this->validateRiskHazardPhotoUpload($request);
        $this->assertRiskAreaSelectionValidity($validated);

        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        if (! $hall) {
            return redirect()
                ->route($returnRoute)
                ->withErrors(['risk_area' => 'Your barangay account is not linked to a barangay hall yet, so risk markers cannot be recorded.']);
        }

        $riskArea = DB::transaction(function () use ($validated, $hall, $request) {
            $structuredNotes = $this->buildRiskAreaStructuredNotes($validated);
            $riskFactor = $this->deriveRiskFactor($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null);
            $conditionLabel = $this->resolveConditionLabel($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null, $validated['condition_code']);
            $legacyHazardType = $this->deriveLegacyHazardType($validated['entry_type'], $validated['hazard_category'] ?? null);
            $legacyMode = $this->deriveLegacyMode($validated['entry_type'], $validated['condition_code'], $validated['hazard_category'] ?? null);
            $markerCategory = $this->deriveMarkerCategory($validated['entry_type']);

            $marker = Marker::create([
                'title' => $validated['title'],
                'description' => trim($validated['location_description'] . ($structuredNotes !== '' ? "\n\n" . $structuredNotes : '')),
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'active',
                'category' => $markerCategory,
            ]);

            $riskArea = RiskArea::create([
                'marker_id' => $marker->marker_id,
                'barangay_hall_id' => $hall->barangay_hall_id,
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
                'population_density_level' => $validated['population_density_level'] ?? null,
                'building_density_level' => $validated['building_density_level'] ?? null,
                'road_accessibility' => $validated['road_accessibility'] ?? null,
                'distance_to_hydrant' => $validated['distance_to_hydrant'] ?? null,
                'distance_to_water_source' => $validated['distance_to_water_source'] ?? null,
                'nearby_establishments' => $validated['nearby_establishments'] ?? null,
                'estimated_affected_households' => $validated['estimated_affected_households'] ?? null,
                'risk_score' => $validated['risk_score'] ?? null,
                'estimated_radius' => $validated['estimated_radius'] ?? null,
                'assessment_level' => $validated['assessment_level'] ?? null,
                'assessment_description' => $validated['assessment_description'] ?? null,
                'remarks' => $validated['remarks'] ?? ($validated['notes'] ?? null),
                'near_hydrant' => (bool) ($validated['near_hydrant'] ?? false),
                'near_water_source' => (bool) ($validated['near_water_source'] ?? false),
                'blocked_hydrant' => (bool) ($validated['blocked_hydrant'] ?? false),
                'informal_settlement' => (bool) ($validated['informal_settlement'] ?? false),
                'cooking_fire_prone_activity' => (bool) ($validated['cooking_fire_prone_activity'] ?? false),
                'combustible_materials_present' => (bool) ($validated['combustible_materials_present'] ?? false),
                'poor_waste_disposal' => (bool) ($validated['poor_waste_disposal'] ?? false),
                'previous_fire_prone_location' => (bool) ($validated['previous_fire_prone_location'] ?? false),
                'status' => $validated['status'],
                'review_status' => 'pending',
                'review_type' => 'create',
                'review_notes' => 'Awaiting chief or administrator verification after barangay submission.',
                'reported_by' => Auth::user()?->user_id,
            ]);

            foreach ($request->file('photos', []) as $photo) {
                if (! $photo) {
                    continue;
                }

                $storedPath = MediaPath::storeForDisk(
                    $photo->store(self::RISK_HAZARD_REPORTS_DIRECTORY, 'supabase'),
                    'supabase'
                );

                RiskAreaPhoto::create([
                    'risk_area_id' => $riskArea->risk_area_id,
                    'photo_path' => $storedPath,
                    'photo_name' => $photo->getClientOriginalName(),
                ]);
            }

            UserActivityLogger::log('submitted risk area', Auth::user()?->user_id, $request);
            return $riskArea->fresh(['marker', 'photos']);
        });

        BarangayNotificationService::notifyRiskAreaSubmitted($riskArea);
        RiskAreaReviewController::flushRiskReviewCache();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Risk area marker submitted successfully.',
                'data' => $this->serializeRiskAreaForBarangayTable($riskArea),
            ]);
        }

        return redirect()
            ->route($returnRoute)
            ->with('success', 'Risk area marker submitted successfully.');
    }

    public function updateRiskArea(Request $request, int $riskAreaId)
    {
        $validated = $request->validate($this->riskAreaValidationRules());
        $this->validateRiskHazardPhotoUpload($request);
        $this->assertRiskAreaSelectionValidity($validated);
        $returnRoute = $request->input('return_to') === 'hazards'
            ? 'barangay.hazards'
            : 'barangay.risk-map';

        $riskArea = $this->resolveOwnedRiskArea($riskAreaId);

        $riskArea = DB::transaction(function () use ($validated, $riskArea, $request) {
            $structuredNotes = $this->buildRiskAreaStructuredNotes($validated);
            $riskFactor = $this->deriveRiskFactor($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null);
            $conditionLabel = $this->resolveConditionLabel($validated['entry_type'], $validated['risk_type'] ?? null, $validated['hazard_category'] ?? null, $validated['condition_code']);
            $legacyHazardType = $this->deriveLegacyHazardType($validated['entry_type'], $validated['hazard_category'] ?? null);
            $legacyMode = $this->deriveLegacyMode($validated['entry_type'], $validated['condition_code'], $validated['hazard_category'] ?? null);
            $markerCategory = $this->deriveMarkerCategory($validated['entry_type']);

            $riskArea->marker?->update([
                'title' => $validated['title'],
                'description' => trim($validated['location_description'] . ($structuredNotes !== '' ? "\n\n" . $structuredNotes : '')),
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'active',
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
                'population_density_level' => $validated['population_density_level'] ?? null,
                'building_density_level' => $validated['building_density_level'] ?? null,
                'road_accessibility' => $validated['road_accessibility'] ?? null,
                'distance_to_hydrant' => $validated['distance_to_hydrant'] ?? null,
                'distance_to_water_source' => $validated['distance_to_water_source'] ?? null,
                'nearby_establishments' => $validated['nearby_establishments'] ?? null,
                'estimated_affected_households' => $validated['estimated_affected_households'] ?? null,
                'risk_score' => $validated['risk_score'] ?? null,
                'estimated_radius' => $validated['estimated_radius'] ?? null,
                'assessment_level' => $validated['assessment_level'] ?? null,
                'assessment_description' => $validated['assessment_description'] ?? null,
                'remarks' => $validated['remarks'] ?? ($validated['notes'] ?? null),
                'near_hydrant' => (bool) ($validated['near_hydrant'] ?? false),
                'near_water_source' => (bool) ($validated['near_water_source'] ?? false),
                'blocked_hydrant' => (bool) ($validated['blocked_hydrant'] ?? false),
                'informal_settlement' => (bool) ($validated['informal_settlement'] ?? false),
                'cooking_fire_prone_activity' => (bool) ($validated['cooking_fire_prone_activity'] ?? false),
                'combustible_materials_present' => (bool) ($validated['combustible_materials_present'] ?? false),
                'poor_waste_disposal' => (bool) ($validated['poor_waste_disposal'] ?? false),
                'previous_fire_prone_location' => (bool) ($validated['previous_fire_prone_location'] ?? false),
                'status' => $validated['status'],
                'review_status' => 'pending',
                'review_type' => 'update',
                'review_notes' => 'Barangay user updated this record. Awaiting chief or administrator review.',
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            foreach ($request->file('photos', []) as $photo) {
                if (! $photo) {
                    continue;
                }

                $storedPath = MediaPath::storeForDisk(
                    $photo->store(self::RISK_HAZARD_REPORTS_DIRECTORY, 'supabase'),
                    'supabase'
                );

                RiskAreaPhoto::create([
                    'risk_area_id' => $riskArea->risk_area_id,
                    'photo_path' => $storedPath,
                    'photo_name' => $photo->getClientOriginalName(),
                ]);
            }

            UserActivityLogger::log('updated risk area', Auth::user()?->user_id, $request);
            return $riskArea->fresh(['marker', 'photos']);
        });

        BarangayNotificationService::notifyRiskAreaSubmitted($riskArea);
        RiskAreaReviewController::flushRiskReviewCache();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Hazard or risk record updated and sent for verification review.',
                'data' => $this->serializeRiskAreaForBarangayTable($riskArea),
            ]);
        }

        return redirect()
            ->route($returnRoute)
            ->with('success', 'Hazard or risk record updated and sent for verification review.');
    }

    public function destroyRiskArea(Request $request, int $riskAreaId)
    {
        $riskArea = $this->resolveOwnedRiskArea($riskAreaId);
        $returnRoute = $request->input('return_to') === 'risk-map'
            ? 'barangay.risk-map'
            : 'barangay.hazards';

        $riskArea->update([
            'review_status' => 'pending',
            'review_type' => 'delete',
            'review_notes' => 'Barangay user requested deletion. Awaiting chief or administrator review.',
            'reviewed_at' => null,
            'reviewed_by' => null,
        ]);

        UserActivityLogger::log('requested risk area deletion', Auth::user()?->user_id, $request);
        BarangayNotificationService::notifyRiskAreaSubmitted($riskArea->fresh(['barangayHall.barangay']));
        RiskAreaReviewController::flushRiskReviewCache();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Delete request sent. The record stays visible until chief or administrator review is completed.',
                'data' => [
                    'id' => $riskArea->risk_area_id,
                    'review_label' => 'DELETE / PENDING',
                    'delete_pending' => true,
                ],
            ]);
        }

        return redirect()
            ->route($returnRoute)
            ->with('success', 'Delete request sent. The record stays visible until chief or administrator review is completed.');
    }

    public function awareness()
    {
        return view('barangay.awareness', $this->buildPortalData());
    }

    public function profile()
    {
        $portalData = $this->buildPortalData();
        $user = User::with(['role', 'address.barangay.city'])->findOrFail(Auth::user()->user_id);
        $cities = City::with('barangays')
            ->orderBy('city_name')
            ->get();
        $context = $this->resolveBarangayContext();
        $barangay = $context['barangay'];
        $hall = $context['hall'];

        $connectedUsers = collect();

        if ($barangay) {
            $connectedUsers = User::query()
                ->with(['role', 'address.barangay.city', 'barangayOfficer'])
                ->where(function ($query) use ($barangay) {
                    $query->whereHas('barangayOfficer', function ($officerQuery) use ($barangay) {
                        $officerQuery->where('barangay_id', $barangay->barangay_id);
                    })->orWhere(function ($addressQuery) use ($barangay) {
                        $addressQuery->whereHas('role', function ($roleQuery) {
                            $roleQuery->whereIn('role', ['barangay', 'barangay captain']);
                        })->whereHas('address', function ($fullAddressQuery) use ($barangay) {
                            $fullAddressQuery->where('barangay_id', $barangay->barangay_id);
                        });
                    });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->unique('user_id')
                ->values();
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'barangay' => [
                        'name' => $barangay?->barangay_name,
                        'city' => $barangay?->city?->city_name,
                        'hall_name' => $hall?->hall_name,
                        'hall_contact_number' => $hall?->contact_number,
                        'hall_email' => $hall?->email,
                    ],
                    'captain_name' => $connectedUsers->first(function ($connectedUser) {
                        return strtolower((string) ($connectedUser->role->role ?? '')) === 'barangay captain';
                    })?->full_name ?? $user->full_name,
                    'stats' => [
                        'connected_users' => $connectedUsers->count(),
                        'active_risk_records' => $portalData['riskAreas']->count(),
                        'fire_records' => $portalData['fireRecords']->count(),
                        'checklist_items' => $portalData['predictionChecklists']->count(),
                    ],
                    'connected_users' => $connectedUsers->map(function (User $connectedUser) {
                        return [
                            'user_id' => $connectedUser->user_id,
                            'full_name' => $connectedUser->full_name ?: trim(($connectedUser->first_name ?? '') . ' ' . ($connectedUser->last_name ?? '')),
                            'initials' => strtoupper(substr($connectedUser->first_name ?? 'B', 0, 1) . substr($connectedUser->last_name ?? ($connectedUser->first_name ?? 'D'), 0, 1)),
                            'email' => $connectedUser->email,
                            'contact_number' => $connectedUser->contact_number,
                            'barangay_name' => $connectedUser->barangayOfficer?->barangay?->barangay_name ?? $connectedUser->address?->barangay?->barangay_name,
                            'role' => $connectedUser->role->role ?? 'barangay',
                            'position' => $connectedUser->barangayOfficer?->position,
                            'connection_type' => $connectedUser->barangayOfficer ? 'assigned' : 'linked',
                        ];
                    })->values(),
                ],
            ]);
        }

        return view('barangay.profile', array_merge(
            $portalData,
            compact('user', 'cities', 'connectedUsers', 'hall', 'barangay')
        ));
    }

    public function updateProfile(Request $request)
    {
        $user = User::with('address.barangay.city')->findOrFail(Auth::user()->user_id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'city_id' => ['required', 'exists:cities,city_id'],
            'barangay_id' => [
                'required',
                Rule::exists('barangays', 'barangay_id')->where(function ($query) use ($request) {
                    $query->where('city_id', $request->city_id);
                }),
            ],
            'street' => ['required', 'string', 'max:255'],
        ]);

        $user->first_name = $validated['first_name'];
        $user->middle_name = $validated['middle_name'] ?? null;
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->contact_number = $validated['contact_number'] ?? null;

        $address = $user->address;

        if ($address) {
            $address->street = $validated['street'];
            $address->barangay_id = $validated['barangay_id'];
            $address->save();
        } else {
            $address = FullAddress::create([
                'street' => $validated['street'],
                'barangay_id' => $validated['barangay_id'],
                'postal_id' => null,
            ]);

            $user->address_id = $address->address_id;
        }

        $user->save();
        UserActivityLogger::log('updated profile', Auth::user()?->user_id, $request);
        $user->load(['role', 'address.barangay.city']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'user' => [
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'contact_number' => $user->contact_number,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'initials' => strtoupper(substr($user->first_name ?: 'B', 0, 1) . substr(($user->last_name ?: $user->first_name ?: 'D'), 0, 1)),
                    'role' => $user->role->role ?? 'Barangay',
                    'street' => $user->address?->street,
                    'barangay' => $user->address?->barangay?->barangay_name,
                    'barangay_id' => $user->address?->barangay?->barangay_id,
                    'city' => $user->address?->barangay?->city?->city_name,
                    'city_id' => $user->address?->barangay?->city?->city_id,
                ],
            ]);
        }

        return redirect()
            ->route('barangay.profile')
            ->with('success', 'Profile updated successfully.');
    }
}
