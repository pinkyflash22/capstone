<?php

namespace App\Http\Controllers\Barangay;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BarangayFireRecord;
use App\Models\BarangayHall;
use App\Models\BarangayPredictionChecklist;
use App\Models\City;
use App\Models\FullAddress;
use App\Models\HistoricalFireIncident;
use App\Models\Incident;
use App\Models\Marker;
use App\Models\Post;
use App\Models\RiskArea;
use App\Models\Station;
use App\Models\User;
use App\Support\UserActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangayDashboardController extends Controller
{
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

    protected function buildPortalData(): array
    {
        $context = $this->resolveBarangayContext();
        /** @var Barangay|null $barangay */
        $barangay = $context['barangay'];
        /** @var BarangayHall|null $hall */
        $hall = $context['hall'];

        $historicalIncidents = collect();
        $predictionChecklists = collect();
        $fireRecords = collect();
        $riskAreas = collect();
        $markers = collect();
        $miniMapPins = collect();
        $stations = Station::query()->orderBy('station_name')->get();

        if ($barangay) {
            $historicalIncidents = HistoricalFireIncident::query()
                ->with(['incident.marker'])
                ->where('barangay_id', $barangay->barangay_id)
                ->orderByDesc('incident_date')
                ->limit(12)
                ->get();

            $predictionChecklists = BarangayPredictionChecklist::query()
                ->with(['assignee', 'reviewer'])
                ->where('barangay_id', $barangay->barangay_id)
                ->orderByRaw("FIELD(priority_level, 'critical', 'high', 'medium', 'low')")
                ->orderBy('target_date')
                ->limit(12)
                ->get();

            $markers = Marker::query()
                ->whereIn('category', ['barangay', 'fire', 'school', 'hydrant', 'bridge', 'station', 'risk'])
                ->orderByDesc('marker_id')
                ->limit(18)
                ->get();
        }

        if ($hall) {
            $fireRecords = BarangayFireRecord::query()
                ->where('barangay_hall_id', $hall->barangay_hall_id)
                ->orderByDesc('incident_date')
                ->limit(12)
                ->get();

            $riskAreas = RiskArea::query()
                ->with(['marker', 'station', 'reporter'])
                ->where('barangay_hall_id', $hall->barangay_hall_id)
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();

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

        $riskAreaCounts = $historicalIncidents
            ->groupBy(function (HistoricalFireIncident $incident) {
                return trim((string) ($incident->incident_location ?: 'Unspecified area'));
            })
            ->map(function (Collection $group) {
                $score = (int) $group->sum(function (HistoricalFireIncident $incident) {
                    return max(1, (int) ($incident->incident_count ?? 1));
                });

                if ($score >= 3) {
                    return 'high';
                }

                if ($score >= 2) {
                    return 'medium';
                }

                return 'low';
            });

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
                            'category' => 'risk',
                            'lat' => (float) $marker->lat,
                            'lng' => (float) $marker->lng,
                            'meta' => strtoupper($riskArea->status ?: 'pending') . ' · ' . str_replace('_', ' ', $riskArea->risk_factor),
                        ];
                    })
                    ->filter()
            )
            ->unique(function (array $pin) {
                return $pin['title'] . '|' . $pin['lat'] . '|' . $pin['lng'];
            })
            ->take(6)
            ->values();

        return array_merge($context, [
            'historicalIncidents' => $historicalIncidents,
            'predictionChecklists' => $predictionChecklists,
            'fireRecords' => $fireRecords,
            'riskAreas' => $riskAreas,
            'markers' => $markers,
            'miniMapPins' => $miniMapPins,
            'latestPosts' => $latestPosts,
            'stations' => $stations,
            'hazardMix' => $hazardMix,
            'statusMix' => $statusMix,
            'priorityMix' => $priorityMix,
            'riskAreaCounts' => [
                'high' => $riskAreaCounts->filter(fn (string $level) => $level === 'high')->count(),
                'medium' => $riskAreaCounts->filter(fn (string $level) => $level === 'medium')->count(),
                'low' => $riskAreaCounts->filter(fn (string $level) => $level === 'low')->count(),
            ],
            'portalStats' => [
                'historical_total' => $historicalIncidents->count(),
                'open_actions' => $predictionChecklists->whereIn('status', ['pending', 'in_progress'])->count(),
                'critical_actions' => $predictionChecklists->whereIn('priority_level', ['critical', 'high'])->count(),
                'recorded_reports' => $fireRecords->count(),
                'risk_markers' => $riskAreas->count(),
                'total_hazards_reported' => (int) $historicalIncidents->sum(function (HistoricalFireIncident $incident) {
                    return max(1, (int) ($incident->incident_count ?? 1));
                }),
                'pending_inspections' => $predictionChecklists->whereIn('status', ['pending', 'in_progress', 'not_complied'])->count(),
            ],
        ]);
    }

    protected function groupCounts(Collection $items, string $field, array $labels): array
    {
        return collect($labels)->mapWithKeys(function (string $label) use ($items, $field) {
            return [$label => $items->where($field, $label)->count()];
        })->all();
    }

    public function dashboard()
    {
        return view('barangay.dashboard', $this->buildPortalData());
    }

    public function hazards()
    {
        return view('barangay.hazards', $this->buildPortalData());
    }

    public function riskMap()
    {
        return view('barangay.risk-map', $this->buildPortalData());
    }

    public function patrols()
    {
        return view('barangay.patrols', $this->buildPortalData());
    }

    public function assessment()
    {
        return view('barangay.assessment', $this->buildPortalData());
    }

    public function reports()
    {
        return view('barangay.reports', $this->buildPortalData());
    }

    public function storeFireReport(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location_description' => ['required', 'string', 'max:1000'],
            'fire_classification' => ['required', Rule::in(['accidental', 'intentional', 'undetermined'])],
            'fire_cause' => ['nullable', 'string', 'max:255'],
            'incident_date' => ['required', 'date'],
            'incident_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location_description' => ['required', 'string', 'max:1000'],
            'risk_factor' => ['required', Rule::in(['people_density', 'housing_material', 'housing_spacing', 'electrical_risk', 'wiring'])],
            'mode' => ['required', Rule::in(['low', 'medium', 'high', 'concrete', 'mixed', 'light_materials', 'dikit_dikit', 'nearly_dikit_dikit', 'moderate', 'far', 'none', 'octopus', 'straight', 'overflow'])],
            'hazard_type' => ['required', Rule::in(['overcrowding', 'electrical', 'flammable', 'none'])],
            'status' => ['required', Rule::in(['pending', 'verified', 'resolved'])],
            'station_id' => ['nullable', 'exists:stations,station_id'],
            'risk_score' => ['nullable', 'numeric', 'between:0,100'],
            'estimated_radius' => ['nullable', 'numeric', 'between:0,10000'],
            'near_hydrant' => ['nullable', 'boolean'],
            'near_water_source' => ['nullable', 'boolean'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $context = $this->resolveBarangayContext();
        $hall = $context['hall'];

        if (! $hall) {
            return redirect()
                ->route('barangay.risk-map')
                ->withErrors(['risk_area' => 'Your barangay account is not linked to a barangay hall yet, so risk markers cannot be recorded.']);
        }

        DB::transaction(function () use ($validated, $hall, $request) {
            $marker = Marker::create([
                'title' => $validated['title'],
                'description' => trim($validated['location_description'] . "\n\n" . ($validated['notes'] ?? '')),
                'lat' => $validated['lat'],
                'lng' => $validated['lng'],
                'status' => 'active',
                'category' => 'risk',
            ]);

            RiskArea::create([
                'marker_id' => $marker->marker_id,
                'barangay_hall_id' => $hall->barangay_hall_id,
                'station_id' => $validated['station_id'] ?? null,
                'risk_factor' => $validated['risk_factor'],
                'mode' => $validated['mode'],
                'hazard_type' => $validated['hazard_type'],
                'risk_score' => $validated['risk_score'] ?? null,
                'estimated_radius' => $validated['estimated_radius'] ?? null,
                'near_hydrant' => (bool) ($validated['near_hydrant'] ?? false),
                'near_water_source' => (bool) ($validated['near_water_source'] ?? false),
                'status' => $validated['status'],
                'reported_by' => Auth::user()?->user_id,
            ]);

            UserActivityLogger::log('submitted risk area', Auth::user()?->user_id, $request);
        });

        return redirect()
            ->route('barangay.risk-map')
            ->with('success', 'Risk area marker submitted successfully.');
    }

    public function awareness()
    {
        return view('barangay.awareness', $this->buildPortalData());
    }

    public function profile()
    {
        $user = User::with(['role', 'address.barangay.city'])->findOrFail(Auth::user()->user_id);
        $cities = City::with('barangays')
            ->orderBy('city_name')
            ->get();

        return view('barangay.profile', array_merge($this->buildPortalData(), compact('user', 'cities')));
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
