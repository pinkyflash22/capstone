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
use App\Models\Marker;
use App\Models\Post;
use App\Models\User;
use App\Services\UserActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        $markers = collect();
        $miniMapPins = collect();

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
                ->whereIn('category', ['barangay', 'fire', 'school', 'hydrant', 'bridge', 'station'])
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
            ->unique(function (array $pin) {
                return $pin['title'] . '|' . $pin['lat'] . '|' . $pin['lng'];
            })
            ->take(6)
            ->values();

        return array_merge($context, [
            'historicalIncidents' => $historicalIncidents,
            'predictionChecklists' => $predictionChecklists,
            'fireRecords' => $fireRecords,
            'markers' => $markers,
            'miniMapPins' => $miniMapPins,
            'latestPosts' => $latestPosts,
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
