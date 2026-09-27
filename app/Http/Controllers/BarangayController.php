<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BarangayHall;
use App\Models\AiActionPlanChecklist;
use App\Models\AiRecommendationSuggestion;
use App\Models\City;
use App\Models\MitigationAction;
use App\Models\Incident;
use App\Models\Marker;
use App\Models\StationOfficer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class BarangayController extends Controller
{
    public function index()
    {
        $chiefCity = $this->isStationAdminRoute() ? $this->getStationAdminCity() : null;
        $barangays = Barangay::with([
                'city.province',
                'hall.marker',
                'hall.address',
            ])
            ->withCount([
                'boundaryLocations',
                'stations',
                'fullAddresses',
                'historicalFireIncidents',
                'predictionChecklists',
                'stationCoverage',
                'barangayOfficers',
                'mitigationActions',
                'aiActionPlanChecklists',
                'aiRecommendationSuggestions',
                'hall',
                'riskAreas',
                'fireRecords',
            ])
            ->when($this->isStationAdminRoute(), function ($query) {
                $chiefCityId = $this->getStationAdminCityId();

                if ($chiefCityId) {
                    $query->where('city_id', $chiefCityId);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->orderBy('barangay_name')
            ->paginate(15);

        if ($this->isStationAdminRoute()) {
            return view('chief.barangays.index', compact('barangays', 'chiefCity'));
        }

        return view('admin.addresses.barangays.index', compact('barangays'));
    }

    public function create()
    {
        $cities = City::with('province')
            ->when($this->isStationAdminRoute(), function ($query) {
                $chiefCityId = $this->getStationAdminCityId();

                if ($chiefCityId) {
                    $query->where('city_id', $chiefCityId);
                    return;
                }

                $query->whereRaw('1 = 0');
            })
            ->orderBy('city_name')
            ->get();

        if ($this->isStationAdminRoute()) {
            return view('chief.barangays.create', compact('cities'));
        }

        return view('admin.addresses.barangays.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barangay_name' => 'required|string|max:255',
            'city_id' => $this->isStationAdminRoute()
                ? ['nullable']
                : [
                    'required',
                    Rule::exists('cities', 'city_id'),
                ],
        ]);

        if ($this->isStationAdminRoute()) {
            $chiefCityId = $this->getStationAdminCityId();

            abort_if(! $chiefCityId, 403);
            $validated['city_id'] = $chiefCityId;
        }

        Barangay::create($validated);

        return redirect()->route($this->scopedBarangayRoute('index'))
            ->with('success', 'Barangay created successfully.');
    }

    public function show(Barangay $barangay)
    {
        if ($this->isStationAdminRoute()) {
            $chiefCityId = $this->getStationAdminCityId();

            abort_if(! $chiefCityId || (int) $barangay->city_id !== (int) $chiefCityId, 403);
        }

        $barangay->load([
            'city.province',
            'fullAddresses.users',
            'hall.marker',
            'hall.address',
            'hall.riskAreas.marker',
            'hall.riskAreas.station',
            'boundaryLocations',
            'historicalFireIncidents',
            'stations.address.barangay.city',
            'barangayOfficers.user.address',
            'mitigationActions',
            'aiActionPlanChecklists',
            'aiRecommendationSuggestions',
            'fireRecords.incident',
            'fireRecords.reporter',
        ]);

        $residentCount = $barangay->fullAddresses
            ->sum(fn ($address) => $address->users->count());

        $liveIncidentCount = 0;

        if (Schema::hasColumn('incidents', 'barangay_id')) {
            $liveIncidentCount = Incident::query()
                ->where('barangay_id', $barangay->barangay_id)
                ->count();
        }

        $summary = [
            'resident_count' => $residentCount,
            'address_count' => $barangay->fullAddresses->count(),
            'risk_area_count' => $barangay->hall?->riskAreas?->count() ?? 0,
            'boundary_count' => $barangay->boundaryLocations->count(),
            'historical_incident_count' => $barangay->historicalFireIncidents->count(),
            'live_incident_count' => $liveIncidentCount,
            'station_count' => $barangay->stations->count(),
        ];

        $recentHistoricalIncidents = $barangay->historicalFireIncidents
            ->sortByDesc('incident_date')
            ->take(8)
            ->values();

        $recentRiskAreas = collect($barangay->hall?->riskAreas ?? [])
            ->sortByDesc('updated_at')
            ->take(8)
            ->values();

        if ($this->isStationAdminRoute()) {
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'barangay_id' => (int) $barangay->barangay_id,
                        'barangay_name' => $barangay->barangay_name,
                        'city_name' => $barangay->city?->city_name,
                        'province_name' => $barangay->city?->province?->province_name,
                        'hall_name' => $barangay->hall?->hall_name,
                        'hall_contact' => $barangay->hall?->contact_number,
                        'hall_email' => $barangay->hall?->email,
                        'hall_coordinates' => [
                            'lat' => $barangay->hall?->marker?->lat,
                            'lng' => $barangay->hall?->marker?->lng,
                        ],
                        'summary' => $summary,
                        'boundary_editor_url' => route('station-admin.maps', array_filter([
                            'open_boundary_editor' => 1,
                            'focus_barangay_id' => $barangay->barangay_id,
                            'focus_barangay_name' => $barangay->barangay_name,
                            'focus_city_id' => $barangay->city?->city_id,
                            'focus_city_name' => $barangay->city?->city_name,
                            'focus_lat' => $barangay->hall?->marker?->lat,
                            'focus_lng' => $barangay->hall?->marker?->lng,
                        ], fn ($value) => $value !== null && $value !== '')),
                        'boundaries' => $barangay->boundaryLocations
                            ->sortByDesc('updated_at')
                            ->map(fn ($boundary) => [
                                'name' => $boundary->name,
                                'source' => strtoupper((string) ($boundary->source ?? 'manual')),
                                'type' => $boundary->boundary_type ?? 'barangay',
                                'updated_at_human' => optional($boundary->updated_at)->diffForHumans() ?? 'Unknown',
                            ])->values(),
                        'stations' => $barangay->stations
                            ->map(fn ($station) => [
                                'station_name' => $station->station_name,
                                'coverage_type' => $station->pivot->coverage_type ?? 'primary',
                                'source' => $station->pivot->source ?? 'system',
                                'address' => $station->address?->street ?? 'No street address',
                            ])->values(),
                        'recent_risk_areas' => $recentRiskAreas
                            ->map(fn ($riskArea) => [
                                'entry' => ucfirst((string) ($riskArea->entry_type ?? 'risk')),
                                'assessment' => $riskArea->assessment_level ?? $riskArea->condition_label ?? 'Not assessed',
                                'status' => $riskArea->status ?? $riskArea->review_status ?? 'Unknown',
                                'updated_at_human' => optional($riskArea->updated_at)->diffForHumans() ?? 'Unknown',
                            ])->values(),
                        'recent_historical_incidents' => $recentHistoricalIncidents
                            ->map(fn ($historicalIncident) => [
                                'date' => optional($historicalIncident->incident_date)->format('M d, Y') ?? 'Unknown',
                                'cause' => $historicalIncident->fire_cause ?? 'Unknown',
                                'location' => $historicalIncident->incident_location ?? 'Unknown',
                                'damage' => $historicalIncident->estimated_damage !== null
                                    ? 'PHP ' . number_format((float) $historicalIncident->estimated_damage, 2)
                                    : 'Not recorded',
                            ])->values(),
                        'barangay_officers' => $barangay->barangayOfficers
                            ->sortBy('position')
                            ->map(fn ($officer) => [
                                'name' => trim(collect([
                                    $officer->user?->first_name,
                                    $officer->user?->middle_name,
                                    $officer->user?->last_name,
                                ])->filter()->implode(' ')) ?: 'Officer #' . $officer->barangay_officer_id,
                                'position' => $officer->position ?? 'Barangay officer',
                                'status' => $officer->status ?? 'Unknown',
                            ])->values(),
                        'mitigation_actions' => $barangay->mitigationActions
                            ->sortByDesc('updated_at')
                            ->take(8)
                            ->map(fn ($action) => [
                                'title' => $action->title ?? 'Mitigation action',
                                'priority' => $action->priority_level ?? 'Normal',
                                'status' => $action->status ?? 'Unknown',
                                'target_date' => optional($action->target_date)->format('M d, Y') ?? 'No target date',
                            ])->values(),
                        'action_plan_checklists' => $barangay->aiActionPlanChecklists
                            ->sortByDesc('updated_at')
                            ->take(8)
                            ->map(fn ($checklist) => [
                                'title' => $checklist->plan_title ?? $checklist->source_title ?? 'Action plan',
                                'priority' => $checklist->priority_level ?? 'Normal',
                                'status' => $checklist->status ?? 'Unknown',
                                'target_date' => optional($checklist->target_date)->format('M d, Y') ?? 'No target date',
                            ])->values(),
                        'recommendations' => $barangay->aiRecommendationSuggestions
                            ->sortByDesc('updated_at')
                            ->take(8)
                            ->map(fn ($suggestion) => [
                                'title' => $suggestion->title ?? $suggestion->recommendation_title ?? 'Recommendation',
                                'status' => $suggestion->status ?? 'Suggested',
                                'updated_at_human' => optional($suggestion->updated_at)->diffForHumans() ?? 'Unknown',
                            ])->values(),
                        'addresses' => $barangay->fullAddresses
                            ->sortBy('street')
                            ->take(8)
                            ->map(fn ($address) => [
                                'street' => $address->street ?? 'Unnamed address',
                                'residents' => $address->users?->count() ?? 0,
                            ])->values(),
                        'fire_records' => $barangay->fireRecords
                            ->sortByDesc('incident_date')
                            ->take(8)
                            ->map(fn ($record) => [
                                'date' => optional($record->incident_date)->format('M d, Y') ?? 'Unknown',
                                'classification' => $record->fire_classification ?? 'Fire record',
                                'cause' => $record->fire_cause ?? 'Unknown cause',
                                'count' => $record->incident_count ?? 1,
                            ])->values(),
                    ],
                ]);
            }

            return view('chief.barangays.show', compact(
                'barangay',
                'summary',
                'recentHistoricalIncidents',
                'recentRiskAreas'
            ));
        }

        return view('admin.barangay-show', compact(
            'barangay',
            'summary',
            'recentHistoricalIncidents',
            'recentRiskAreas'
        ));
    }

    public function edit(Barangay $barangay)
    {
        $cities = City::with('province')->get();
        return view('admin.addresses.barangays.edit', compact('barangay', 'cities'));
    }

    public function update(Request $request, Barangay $barangay)
    {
        if ($this->isStationAdminRoute()) {
            $chiefCityId = $this->getStationAdminCityId();

            abort_if(! $chiefCityId || (int) $barangay->city_id !== (int) $chiefCityId, 403);
        }

        $validated = $request->validate([
            'barangay_name' => 'required|string|max:255',
            'hall_name' => ['nullable', 'string', 'max:255'],
            'hall_contact_number' => ['nullable', 'string', 'max:60'],
            'hall_email' => ['nullable', 'email:rfc', 'max:255'],
            'marker_title' => ['nullable', 'string', 'max:255'],
            'marker_description' => ['nullable', 'string', 'max:1000'],
            'marker_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'marker_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'marker_status' => ['nullable', Rule::in(['active', 'inactive', 'completed'])],
            'barangay_card_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'city_id' => $this->isStationAdminRoute()
                ? ['nullable']
                : [
                    'required',
                    Rule::exists('cities', 'city_id'),
                ],
        ]);

        if ($this->isStationAdminRoute()) {
            $validated['city_id'] = $this->getStationAdminCityId();
        }

        $wantsMarkerCreated = $request->hasFile('barangay_card_image') || collect([
            $validated['marker_title'] ?? null,
            $validated['marker_description'] ?? null,
            $request->input('marker_lat'),
            $request->input('marker_lng'),
        ])->contains(fn ($value) => filled($value));

        if ($wantsMarkerCreated
            && (! filled($request->input('marker_lat')) || ! filled($request->input('marker_lng')))
            && ! $barangay->loadMissing('hall.marker')->hall?->marker
        ) {
            throw ValidationException::withMessages([
                'marker_lat' => 'Latitude and longitude are required before creating a barangay hall marker.',
                'marker_lng' => 'Latitude and longitude are required before creating a barangay hall marker.',
            ]);
        }

        DB::transaction(function () use ($barangay, $validated, $request) {
            $barangay->update([
                'barangay_name' => $validated['barangay_name'],
                'city_id' => $validated['city_id'] ?? $barangay->city_id,
            ]);

            $barangay->loadMissing('hall.marker');

            $hallInputs = [
                'hall_name' => $validated['hall_name'] ?? null,
                'contact_number' => $validated['hall_contact_number'] ?? null,
                'email' => $validated['hall_email'] ?? null,
            ];

            $hasHallInputs = $request->hasAny(['hall_name', 'hall_contact_number', 'hall_email']);
            $hasMarkerInputs = $request->hasAny(['marker_title', 'marker_description', 'marker_lat', 'marker_lng', 'marker_status']);
            $wantsMarkerCreated = $request->hasFile('barangay_card_image') || collect([
                $validated['marker_title'] ?? null,
                $validated['marker_description'] ?? null,
                $request->input('marker_lat'),
                $request->input('marker_lng'),
            ])->contains(fn ($value) => filled($value));

            $hall = $barangay->hall;
            if (! $hall && ($hasHallInputs || $hasMarkerInputs || $request->hasFile('barangay_card_image'))) {
                $hall = BarangayHall::create([
                    'barangay_id' => $barangay->barangay_id,
                    'hall_name' => ($validated['hall_name'] ?? null) ?: $barangay->barangay_name . ' Barangay Hall',
                    'contact_number' => $validated['hall_contact_number'] ?? null,
                    'email' => $validated['hall_email'] ?? null,
                ]);
            } elseif ($hall && $hasHallInputs) {
                $hall->update([
                    'hall_name' => ($validated['hall_name'] ?? null) ?: $hall->hall_name,
                    'contact_number' => $validated['hall_contact_number'] ?? null,
                    'email' => $validated['hall_email'] ?? null,
                ]);
            }

            $marker = $hall?->marker;
            if (! $marker && $hall && $wantsMarkerCreated && filled($request->input('marker_lat')) && filled($request->input('marker_lng'))) {
                $marker = Marker::create([
                    'title' => ($validated['marker_title'] ?? null) ?: ($hall->hall_name ?: $barangay->barangay_name . ' Barangay Hall'),
                    'description' => $validated['marker_description'] ?? null,
                    'lat' => $validated['marker_lat'],
                    'lng' => $validated['marker_lng'],
                    'status' => $validated['marker_status'] ?? 'active',
                    'category' => 'barangay',
                ]);

                $hall->update(['marker_id' => $marker->marker_id]);
            } elseif ($marker && $hasMarkerInputs) {
                $marker->update([
                    'title' => $validated['marker_title'] ?: $marker->title,
                    'description' => $validated['marker_description'] ?? null,
                    'lat' => $validated['marker_lat'] ?? $marker->lat,
                    'lng' => $validated['marker_lng'] ?? $marker->lng,
                    'status' => $validated['marker_status'] ?? $marker->status,
                    'category' => 'barangay',
                ]);
            }

            if ($request->hasFile('barangay_card_image') && $marker) {
                $path = $request->file('barangay_card_image')->store('barangays', 'public');
                $marker->forceFill(['image' => $path])->save();
            }
        });

        $redirectRoute = $this->isStationAdminRoute() && $request->input('return_to') === 'show'
            ? route('station-admin.barangays.show', $barangay)
            : route($this->scopedBarangayRoute('index'));

        return redirect($redirectRoute)
            ->with('success', 'Barangay updated successfully.');
    }

    public function destroy(Request $request, Barangay $barangay)
    {
        if ($this->isStationAdminRoute()) {
            $chiefCityId = $this->getStationAdminCityId();

            abort_if(! $chiefCityId || (int) $barangay->city_id !== (int) $chiefCityId, 403);
        }

        $connectionSummary = $this->getBarangayConnectionSummary($barangay);
        $hasConnectedRecords = collect($connectionSummary)->sum() > 0;

        if ($hasConnectedRecords && ! $request->boolean('force_delete_related')) {
            return redirect()->route($this->scopedBarangayRoute('index'))
                ->with('error', 'This barangay has connected records. Review the delete warning and choose Proceed Deletion to remove the connected barangay records too.');
        }

        DB::transaction(function () use ($barangay) {
            $barangay->loadMissing([
                'hall',
                'riskAreas',
            ]);

            $markerIdsToDelete = collect();

            if ($barangay->hall?->marker_id) {
                $markerIdsToDelete->push((int) $barangay->hall->marker_id);
            }

            $riskAreaMarkerIds = $barangay->riskAreas
                ->pluck('marker_id')
                ->filter()
                ->map(fn ($markerId) => (int) $markerId);

            $markerIdsToDelete = $markerIdsToDelete
                ->merge($riskAreaMarkerIds)
                ->unique()
                ->values();

            AiRecommendationSuggestion::query()
                ->where('barangay_id', $barangay->barangay_id)
                ->delete();

            AiActionPlanChecklist::query()
                ->where('barangay_id', $barangay->barangay_id)
                ->delete();

            MitigationAction::query()
                ->where('barangay_id', $barangay->barangay_id)
                ->delete();

            $barangay->delete();

            $this->deleteUnusedMarkers($markerIdsToDelete->all());
        });

        return redirect()->route($this->scopedBarangayRoute('index'))
            ->with('success', 'Barangay deleted successfully.');
    }

    public function getByCity($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)->get();
        return response()->json($barangays);
    }

    private function isStationAdminRoute(): bool
    {
        return str_starts_with((string) request()->route()?->getName(), 'station-admin.');
    }

    private function getStationAdminCityId(): ?int
    {
        if (! $this->isStationAdminRoute()) {
            return null;
        }

        $chiefCityId = $this->getStationAdminCity()?->city_id;

        return $chiefCityId ? (int) $chiefCityId : null;
    }

    private function getStationAdminCity(): ?City
    {
        if (! $this->isStationAdminRoute()) {
            return null;
        }

        $userId = Auth::user()?->user_id ?? Auth::id();

        if (! $userId) {
            return null;
        }

        $stationOfficer = StationOfficer::query()
            ->with('station.address.barangay.city.province')
            ->where('user_id', $userId)
            ->first();

        return $stationOfficer?->station?->address?->barangay?->city;
    }

    private function scopedBarangayRoute(string $action): string
    {
        $routeName = (string) request()->route()?->getName();

        if (str_starts_with($routeName, 'station-admin.')) {
            return "station-admin.barangays.{$action}";
        }

        if (str_starts_with($routeName, 'admin.')) {
            return "admin.barangays.{$action}";
        }

        return "barangays.{$action}";
    }

    private function getBarangayConnectionSummary(Barangay $barangay): array
    {
        $barangay->loadCount([
            'fullAddresses',
            'boundaryLocations',
            'historicalFireIncidents',
            'predictionChecklists',
            'stationCoverage',
            'barangayOfficers',
            'mitigationActions',
            'aiActionPlanChecklists',
            'aiRecommendationSuggestions',
            'hall',
            'riskAreas',
            'fireRecords',
        ]);

        return [
            'addresses' => (int) ($barangay->full_addresses_count ?? 0),
            'boundary_records' => (int) ($barangay->boundary_locations_count ?? 0),
            'historical_incidents' => (int) ($barangay->historical_fire_incidents_count ?? 0),
            'prediction_checklists' => (int) ($barangay->prediction_checklists_count ?? 0),
            'station_coverage_links' => (int) ($barangay->station_coverage_count ?? 0),
            'barangay_officers' => (int) ($barangay->barangay_officers_count ?? 0),
            'mitigation_actions' => (int) ($barangay->mitigation_actions_count ?? 0),
            'action_plan_checklists' => (int) ($barangay->ai_action_plan_checklists_count ?? 0),
            'recommendation_suggestions' => (int) ($barangay->ai_recommendation_suggestions_count ?? 0),
            'barangay_hall' => (int) ($barangay->hall_count ?? 0),
            'risk_areas' => (int) ($barangay->risk_areas_count ?? 0),
            'fire_records' => (int) ($barangay->fire_records_count ?? 0),
        ];
    }

    private function deleteUnusedMarkers(array $markerIds): void
    {
        $markerIds = collect($markerIds)
            ->filter()
            ->map(fn ($markerId) => (int) $markerId)
            ->unique()
            ->values();

        if ($markerIds->isEmpty()) {
            return;
        }

        $usedMarkerIds = collect();

        foreach (['stations', 'hydrants', 'incidents', 'barangay_halls', 'risk_areas'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'marker_id')) {
                continue;
            }

            $usedMarkerIds = $usedMarkerIds->merge(
                DB::table($table)->whereIn('marker_id', $markerIds)->pluck('marker_id')
            );
        }

        $usedMarkerIds = $usedMarkerIds
            ->filter()
            ->map(fn ($markerId) => (int) $markerId)
            ->unique();

        $unusedMarkerIds = $markerIds->diff($usedMarkerIds)->values();

        if ($unusedMarkerIds->isNotEmpty()) {
            Marker::query()
                ->whereIn('marker_id', $unusedMarkerIds->all())
                ->delete();
        }
    }
}
