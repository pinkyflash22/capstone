<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marker;
use App\Models\Incident;

use App\Models\StationOfficer;
use App\Models\Station;
use App\Models\FsicRequest;
use App\Models\InspectionChecklist;
use App\Models\FsicApproval;
use App\Models\FsicInspection;
use App\Models\Province;
use App\Models\User;
use App\Models\City;
use App\Models\FullAddress;
use App\Support\UserActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    private const CITIZEN_DASHBOARD_SECTIONS = [
        'overview',
        'alerts',
        'incidents',
        'fsic',
        'contacts',
        'announcements',
        'safety',
        'neighborhood',
        'events',
        'news',
        'profile',
        'notifications',
    ];

    public function pwaQuickReport()
    {
        return view('pwa.quick-report');
    }

    public static function citizenDashboardSections(): array
    {
        return self::CITIZEN_DASHBOARD_SECTIONS;
    }

    private function buildCitizenWeeklyActivity($incidents): array
    {
        $today = now();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $previousWeekStart = $startOfWeek->copy()->subWeek();
        $previousWeekEnd = $endOfWeek->copy()->subWeek();

        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $currentWeekCounts = array_fill(0, 7, 0);

        foreach ($incidents as $incident) {
            if (!$incident->created_at) {
                continue;
            }

            $createdAt = $incident->created_at->copy();

            if ($createdAt->between($startOfWeek, $endOfWeek)) {
                $dayIndex = max(0, min(6, (int) $createdAt->dayOfWeekIso - 1));
                $currentWeekCounts[$dayIndex]++;
            }
        }

        $currentWeekTotal = array_sum($currentWeekCounts);
        $previousWeekTotal = $incidents->filter(function ($incident) use ($previousWeekStart, $previousWeekEnd) {
            return $incident->created_at && $incident->created_at->between($previousWeekStart, $previousWeekEnd);
        })->count();

        return [
            'labels' => $labels,
            'counts' => $currentWeekCounts,
            'total' => $currentWeekTotal,
            'max' => max(max($currentWeekCounts), 1),
            'change' => $currentWeekTotal - $previousWeekTotal,
            'period_label' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d'),
        ];
    }

    private function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function determineRiskLevel(float $riskPercentage): string
    {
        if ($riskPercentage >= 70) {
            return 'high';
        }

        if ($riskPercentage >= 35) {
            return 'moderate';
        }

        return 'low';
    }

    private function resolveCitizenIncidentTypeId(Incident $incident): int
    {
        return 1;
    }

    private function resolveCitizenIncidentTypeName(Incident $incident): string
    {
        return 'Fire';
    }

    private function resolveCitizenIncidentStatusId(Incident $incident): int
    {
        return match (strtolower(trim((string) ($incident->status ?? '')))) {
            'responding', 'en-route', 'en route' => 2,
            'on scene', 'on-scene' => 3,
            'controlled', 'completed' => 4,
            'closed' => 5,
            default => 1,
        };
    }

    private function serializeCitizenIncident(Incident $incident, ?float $distanceMeters = null): array
    {
        return [
            'incident_id' => $incident->incident_id,
            'status'=> $incident->status,
            'incident_status' => $incident->status,
            'incident_status_id' => $this->resolveCitizenIncidentStatusId($incident),
            'incident_type' => $this->resolveCitizenIncidentTypeName($incident),
            'incident_type_name' => $this->resolveCitizenIncidentTypeName($incident),
            'incident_type_id' => $this->resolveCitizenIncidentTypeId($incident),
            'created_at' => optional($incident->created_at)->toISOString(),
            'created_at_human' => optional($incident->created_at)?->diffForHumans(),
            'marker_id' => $incident->marker_id,
            'lat' => $incident->marker?->lat,
            'lng' => $incident->marker?->lng,
            'address' => $incident->marker?->description,
            'marker' => $incident->marker,
            'barangay_id' => $incident->barangay_id,
            'barangay_name' => $incident->barangay?->barangay_name,
            'barangay_city_name' => $incident->barangay?->city?->city_name,
            'barangay_resolution_status' => $incident->barangay_resolution_status ?? 'pending',
            'barangay_resolution_source' => $incident->barangay_resolution_source,
            'barangay_resolved_at' => optional($incident->barangay_resolved_at)->toISOString(),
            'distance_meters' => $distanceMeters !== null ? round($distanceMeters) : null,
        ];
    }

    private function buildCitizenDashboardData(int $accountId, ?float $latitude = null, ?float $longitude = null): array
    {
        $incidents = Incident::with(['marker', 'barangay.city'])
            ->orderByDesc('created_at')
            ->get();

        $userIncidents = Incident::with(['marker', 'barangay.city'])
            ->where('reported_by', $accountId)
            ->orderByDesc('created_at')
            ->get();

        $userReports = $userIncidents->count();
        $countReport = $userReports;
        $fireCount = Incident::where('status', 'Reported')->count();
        $totalIncidentCount = max($incidents->count(), 1);
        $riskPercentage = round(($fireCount / $totalIncidentCount) * 100, 1);
        $riskLevel = $this->determineRiskLevel($riskPercentage);
        $weeklyActivity = $this->buildCitizenWeeklyActivity($userIncidents);
        $requestsCount = FsicRequest::where('user_id', $accountId)->count();

        $nearbyIncidents = collect();

        if ($latitude !== null && $longitude !== null) {
            $nearbyIncidents = $incidents
                ->filter(function ($incident) use ($latitude, $longitude) {
                    if (!$incident->marker || $incident->marker->lat === null || $incident->marker->lng === null) {
                        return false;
                    }

                    $distance = $this->calculateDistanceMeters(
                        $latitude,
                        $longitude,
                        (float) $incident->marker->lat,
                        (float) $incident->marker->lng
                    );

                    $incident->computed_distance_meters = $distance;

                    return $distance <= 3000;
                })
                ->sortBy([
                    ['computed_distance_meters', 'asc'],
                    ['created_at', 'desc'],
                ])
                ->values();
        }

        $activeNearbyIncidents = $nearbyIncidents->filter(function ($incident) {
            $status = strtolower(trim($incident->status ?? ''));
            return in_array($status, ['reported', 'responding', 'on scene'], true);
        })->values();

        return [
            'summary' => [
                'active_near_you' => $activeNearbyIncidents->count(),
                'your_reports' => $userReports,
                'total_reports' => $countReport,
                'active_alerts' => $activeNearbyIncidents->count(),
                'risk_percentage' => $riskPercentage,
                'risk_level' => $riskLevel,
            ],
            'incidents' => $incidents->map(fn ($incident) => $this->serializeCitizenIncident($incident))->values(),
            'user_incidents' => $userIncidents->map(fn ($incident) => $this->serializeCitizenIncident($incident))->values(),
            'nearby_incidents' => $activeNearbyIncidents
                ->map(fn ($incident) => $this->serializeCitizenIncident($incident, $incident->computed_distance_meters ?? null))
                ->values(),
            'weekly_activity' => $weeklyActivity,
            'requests_count' => $requestsCount,
        ];
    }

    //note dapat kapag mag papass sa isang view kung lahat ng data kailangan mo, idaan mo nalang sa isang controller
    public function index(?string $section = null)
    {
        $accountId = Auth::user()->user_id;
        $initialSection = in_array($section, self::CITIZEN_DASHBOARD_SECTIONS, true) ? $section : 'overview';

        $incidents = Incident::with(['marker', 'barangay.city'])->get();
        $userReports = Incident::where('reported_by', $accountId)->count();
        $countReport = Incident::where('reported_by', $accountId)->count();
        $userinfo = User::where('user_id', $accountId)->with('role','address','address.barangay','address.barangay.city')->get();
        $usermarker =  Marker::all();
        $provinces = Province::with('cities.barangays')->get();
        $cities = City::with('barangays')->orderBy('city_name')->get(['city_id', 'city_name']);
        $citizenUser = User::with(['role', 'address.barangay.city'])->findOrFail($accountId);
          $myReports = Incident::where('reported_by',$accountId)
          ->with(['marker', 'barangay.city'])
           ->orderBy('created_at', 'desc')
          ->get();

        $allStationOfficer = StationOfficer::with(['user'])->get();
      
        $stations = Station::with(['marker','address'])->get();
        $dashboardData = $this->buildCitizenDashboardData($accountId);
        $riskPercentage = $dashboardData['summary']['risk_percentage'];
        $riskLevel = $dashboardData['summary']['risk_level'];
        $activeAlerts = $dashboardData['summary']['active_alerts'];
        $totalAlerts = $activeAlerts;
        $nearbyIncidents = collect($dashboardData['nearby_incidents']);


        //request
        $fsicApplications = FsicRequest::all();
        $fsicRequests = FsicRequest::where('user_id', $accountId)->get();

        return view('citizen.dashboard', compact('usermarker','myReports','countReport','stations','fsicApplications','fsicRequests',
        'provinces','cities','citizenUser','allStationOfficer', 'accountId','incidents','userReports','userinfo', 'riskPercentage', 'riskLevel', 'activeAlerts', 'totalAlerts', 'nearbyIncidents', 'initialSection'));
        // return view('citizen.dashboard', compact('usermarker'));

    }

    public function dashboardData(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $accountId = Auth::user()?->user_id;
        $latitude = isset($validated['lat']) ? (float) $validated['lat'] : null;
        $longitude = isset($validated['lng']) ? (float) $validated['lng'] : null;

        return response()->json([
            'success' => true,
            'data' => $this->buildCitizenDashboardData($accountId, $latitude, $longitude),
        ]);
    }

    public function mobileHub()
    {
        $user = Auth::user();

        abort_unless($user && strtolower($user->role->role ?? '') === 'citizen', 403);

        return view('citizen.mobile-hub', [
            'displayName' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Citizen',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = User::with('address')->findOrFail(Auth::id());

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
                    'initials' => strtoupper(substr($user->first_name ?: 'C', 0, 1) . substr(($user->last_name ?: $user->first_name ?: 'P'), 0, 1)),
                    'role' => $user->role->role ?? 'Citizen',
                    'street' => $user->address?->street,
                    'barangay' => $user->address?->barangay?->barangay_name,
                    'barangay_id' => $user->address?->barangay?->barangay_id,
                    'city' => $user->address?->barangay?->city?->city_name,
                    'city_id' => $user->address?->barangay?->city?->city_id,
                ],
            ]);
        }

        return redirect()
            ->route('citizen.dashboard', ['section' => 'profile'])
            ->with('success', 'Profile updated successfully.')
            ->with('open_section', 'profile');
    }


    public function homepage(){

    return view('dashboard');
    }
        //     public function dashboard()
        // {
        //     $accountId = Auth::user()->account->account_id;

        //     return view('citizen.dashboard', compact('accountId'));
        // }
}
