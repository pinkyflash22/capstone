<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\BarangayHall;
use App\Models\City;
use App\Models\CommunityActivityRequest;
use App\Models\FsicRequest;
use App\Models\FullAddress;
use App\Models\Incident;
use App\Models\LandingMedia;
use App\Models\Marker;
use App\Models\Province;
use App\Models\RiskArea;
use App\Models\RiskAreaPhoto;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\User;
use App\Services\BoundaryMatcherService;
use App\Support\MediaPath;
use App\Services\ProfileImageService;
use App\Support\RiskAreaFollowUpDispatcher;
use App\Support\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PublicController extends Controller
{
    private const CITIZEN_NEARBY_INCIDENT_RADIUS_METERS = 3000;

    private const RISK_HAZARD_REPORTS_DIRECTORY = 'riskhazard';

    private const RISK_HAZARD_REPORTS_MAX_FILES = 3;

    private const RISK_HAZARD_REPORTS_MAX_TOTAL_BYTES = 5242880;

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
        $user = Auth::user();
        $role = strtolower((string) ($user?->role->role ?? ''));

        if ($user && $role === 'citizen') {
            return redirect()->route('citizen.mobile-hub');
        }

        // Keep the installed PWA boot route cache-safe for offline launch.
        // Non-citizen users and guests can still open the public landing shell
        // even when the network is unavailable.
        return $this->homepage();
    }

    public static function citizenDashboardSections(): array
    {
        return self::CITIZEN_DASHBOARD_SECTIONS;
    }

    private function buildCitizenWeeklyActivity(Collection $incidents): array
    {
        $today = now();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $previousWeekStart = $startOfWeek->copy()->subWeek();
        $previousWeekEnd = $endOfWeek->copy()->subWeek();

        $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $currentWeekCounts = array_fill(0, 7, 0);

        foreach ($incidents as $incident) {
            if (! $incident->created_at) {
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
            'period_label' => $startOfWeek->format('M d').' - '.$endOfWeek->format('M d'),
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

    private function formatCitizenLabel(?string $value, string $fallback = 'Unknown'): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return $fallback;
        }

        return ucwords(str_replace(['_', '-'], ' ', $normalized));
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
        $status = (string) ($incident->status ?? 'Reported');
        $dispatch = app(\App\Services\IncidentDispatchStateService::class)->describe($incident);

        return [
            ...$dispatch,
            'incident_id' => $incident->incident_id,
            'reported_by' => $incident->reported_by,
            'status' => $status,
            'incident_status' => $status,
            'incident_status_id' => $this->resolveCitizenIncidentStatusId($incident),
            'incident_type' => $this->resolveCitizenIncidentTypeName($incident),
            'incident_type_name' => $this->resolveCitizenIncidentTypeName($incident),
            'incident_type_id' => $this->resolveCitizenIncidentTypeId($incident),
            'report_type' => 'fire',
            'report_type_label' => 'Fire Incident',
            'type_label' => 'Fire Incident',
            'category_label' => 'Fire',
            'status_label' => $this->formatCitizenLabel($status, 'Reported'),
            'created_at' => optional($incident->created_at)->toISOString(),
            'created_at_human' => optional($incident->created_at)?->diffForHumans(),
            'marker_id' => $incident->marker_id,
            'lat' => $incident->marker?->lat,
            'lng' => $incident->marker?->lng,
            'address' => $incident->marker?->description,
            'location_label' => $incident->marker?->description ?: 'Location pending',
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

    private function serializeCitizenRiskReport(RiskArea $riskArea): array
    {
        $entryType = strtolower(trim((string) ($riskArea->entry_type ?? 'risk')));
        $reviewStatus = (string) ($riskArea->review_status ?? 'pending');
        $status = (string) ($riskArea->status ?? 'pending');
        $typeLabel = $entryType === 'hazard' ? 'Risk/Hazard Factor' : 'Risk/Hazard Factor';
        $categoryLabel = $entryType === 'hazard'
            ? $this->formatCitizenLabel($riskArea->hazard_category ?? $riskArea->hazard_type, 'Hazard')
            : $this->formatCitizenLabel($riskArea->risk_type ?? $riskArea->condition_label, 'Risk');
        $photoUrls = $riskArea->photos
            ->map(fn ($photo) => [
                'url' => MediaPath::url($photo->photo_path),
                'name' => $photo->photo_name,
                'caption' => $photo->caption,
            ])
            ->filter(fn ($photo) => ! empty($photo['url']))
            ->values();

        return [
            'risk_area_id' => $riskArea->risk_area_id,
            'created_at' => optional($riskArea->created_at)->toISOString(),
            'created_at_human' => optional($riskArea->created_at)?->diffForHumans(),
            'report_kind' => $entryType,
            'entry_type' => $entryType,
            'category' => $entryType,
            'type' => $entryType === 'hazard' ? 'Hazard' : 'Risk',
            'report_type' => $entryType,
            'report_type_label' => $typeLabel,
            'type_label' => $entryType === 'hazard' ? 'Hazard Factor' : 'Risk Factor',
            'category_label' => $categoryLabel,
            'title' => $riskArea->marker?->title ?? ($entryType === 'hazard' ? 'Citizen Hazard Report' : 'Citizen Risk Report'),
            'description' => $riskArea->marker?->description ?? $riskArea->remarks ?? $riskArea->assessment_description,
            'condition_label' => $riskArea->condition_label,
            'status' => $status,
            'status_label' => $this->formatCitizenLabel($status, 'Pending'),
            'review_status' => $reviewStatus,
            'review_status_label' => $this->formatCitizenLabel($reviewStatus, 'Pending'),
            'review_type' => $riskArea->review_type ?? 'create',
            'remarks' => $riskArea->remarks,
            'assessment_description' => $riskArea->assessment_description,
            'street_or_sitio' => $riskArea->street_or_sitio,
            'location_label' => $riskArea->street_or_sitio ?: ($riskArea->marker?->description ?: 'General area'),
            'lat' => $riskArea->marker?->lat,
            'lng' => $riskArea->marker?->lng,
            'barangay_name' => $riskArea->barangayHall?->barangay?->barangay_name,
            'barangay_city_name' => $riskArea->barangayHall?->barangay?->city?->city_name,
            'marker_id' => $riskArea->marker_id,
            'marker_category' => $riskArea->marker?->category,
            'risk_type' => $riskArea->risk_type,
            'hazard_category' => $riskArea->hazard_category,
            'hazard_type' => $riskArea->hazard_type,
            'photos' => $photoUrls,
            'photo_url' => $photoUrls->first()['url'] ?? null,
        ];
    }

    private function serializeCitizenRiskMapRecord(RiskArea $riskArea): array
    {
        $report = $this->serializeCitizenRiskReport($riskArea);

        return array_merge($report, [
            'id' => $riskArea->risk_area_id,
            'popup_title' => $report['title'],
            'popup_subtitle' => $report['category_label'],
        ]);
    }

    private function resolveCitizenQuickRiskTemplate(string $reportKind): array
    {
        if ($reportKind === 'hazard') {
            return [
                'title' => 'Citizen Hazard Concern',
                'location_description' => 'Citizen-submitted on-site hazard concern awaiting BFP and barangay verification.',
                'entry_type' => 'hazard',
                'hazard_category' => 'electrical_hazard',
                'risk_type' => null,
                'condition_code' => 'exposed_wires',
                'marker_category' => 'hazard',
                'risk_factor' => 'electrical_risk',
                'mode' => 'high',
                'condition_label' => 'Possible on-site hazard',
                'hazard_type' => 'electrical',
                'review_notes' => 'Awaiting BFP or administrator verification. Final classification may be adjusted after review.',
            ];
        }

        return [
            'title' => 'Citizen Area Risk Concern',
            'location_description' => 'Citizen-submitted area fire-risk concern awaiting BFP and barangay verification.',
            'entry_type' => 'risk',
            'hazard_category' => null,
            'risk_type' => 'historical_fire_prone_risk',
            'condition_code' => 'known_fire_prone_location',
            'marker_category' => 'risk',
            'risk_factor' => 'wiring',
            'mode' => 'moderate',
            'condition_label' => 'Possible fire-prone area',
            'hazard_type' => 'none',
            'review_notes' => 'Awaiting BFP or administrator verification. Final classification may be adjusted after review.',
        ];
    }

    private function resolveCitizenConcernTemplate(string $concernType): array
    {
        return match ($concernType) {
            'electrical_issue' => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Possible electrical fire hazard reported by a citizen.',
                'entry_type' => 'hazard',
                'hazard_category' => 'electrical_hazard',
                'risk_type' => null,
                'risk_factor' => 'electrical_risk',
                'mode' => 'high',
                'condition_code' => 'electrical_issue',
                'condition_label' => 'Possible electrical hazard',
                'hazard_type' => 'electrical',
                'marker_category' => 'hazard',
                'review_notes' => 'Citizen reported a possible electrical hazard. Awaiting BFP verification and classification review.',
            ],
            'unsafe_cooking_lpg' => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Possible unsafe cooking or LPG setup reported by a citizen.',
                'entry_type' => 'hazard',
                'hazard_category' => 'flammable_hazard',
                'risk_type' => null,
                'risk_factor' => 'wiring',
                'mode' => 'high',
                'condition_code' => 'unsafe_cooking_lpg',
                'condition_label' => 'Possible unsafe cooking or LPG setup',
                'hazard_type' => 'flammable',
                'marker_category' => 'hazard',
                'review_notes' => 'Citizen reported a possible LPG or cooking-related hazard. Awaiting BFP verification and classification review.',
            ],
            'blocked_hydrant_access' => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Possible blocked hydrant or poor water access reported by a citizen.',
                'entry_type' => 'risk',
                'hazard_category' => null,
                'risk_type' => 'hydrant_access_risk',
                'risk_factor' => 'wiring',
                'mode' => 'moderate',
                'condition_code' => 'blocked_hydrant_access',
                'condition_label' => 'Possible hydrant or water-access risk',
                'hazard_type' => 'none',
                'marker_category' => 'risk',
                'review_notes' => 'Citizen reported a possible hydrant or water-access concern. Awaiting BFP verification and classification review.',
            ],
            'combustible_clutter' => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Possible combustible clutter or waste buildup reported by a citizen.',
                'entry_type' => 'risk',
                'hazard_category' => null,
                'risk_type' => 'combustible_material_risk',
                'risk_factor' => 'housing_material',
                'mode' => 'moderate',
                'condition_code' => 'combustible_clutter',
                'condition_label' => 'Possible combustible clutter or waste buildup',
                'hazard_type' => 'none',
                'marker_category' => 'risk',
                'review_notes' => 'Citizen reported possible combustible clutter or waste buildup. Awaiting BFP verification and classification review.',
            ],
            'dense_fire_prone_area' => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Possible fire-prone area condition reported by a citizen.',
                'entry_type' => 'risk',
                'hazard_category' => null,
                'risk_type' => 'historical_fire_prone_risk',
                'risk_factor' => 'housing_spacing',
                'mode' => 'moderate',
                'condition_code' => 'dense_fire_prone_area',
                'condition_label' => 'Possible fire-prone area condition',
                'hazard_type' => 'none',
                'marker_category' => 'risk',
                'review_notes' => 'Citizen reported a possible fire-prone area condition. Awaiting BFP verification and classification review.',
            ],
            default => [
                'title' => 'Citizen Safety Concern',
                'location_description' => 'Citizen-submitted safety concern awaiting BFP verification.',
                'entry_type' => 'risk',
                'hazard_category' => null,
                'risk_type' => 'general_fire_safety_concern',
                'risk_factor' => 'wiring',
                'mode' => 'moderate',
                'condition_code' => 'general_safety_concern',
                'condition_label' => 'General fire safety concern',
                'hazard_type' => 'none',
                'marker_category' => 'risk',
                'review_notes' => 'Citizen was unsure whether the concern was a risk or hazard. Awaiting BFP verification and final classification.',
            ],
        };
    }

    private function buildCitizenDashboardData(
        int $accountId,
        ?float $latitude = null,
        ?float $longitude = null,
        $preloadedIncidents = null,
        $preloadedUserIncidents = null
    ): array {
        $incidents = $preloadedIncidents instanceof \Illuminate\Support\Collection
            ? $preloadedIncidents
            : Incident::with(['marker', 'station', 'barangay.city'])
                ->orderByDesc('created_at')
                ->get();

        $userIncidents = $preloadedUserIncidents instanceof \Illuminate\Support\Collection
            ? $preloadedUserIncidents
            : Incident::with(['marker', 'station', 'barangay.city'])
                ->where('reported_by', $accountId)
                ->orderByDesc('created_at')
                ->get();

        $userRiskReports = RiskArea::with(['marker', 'photos', 'barangayHall.barangay.city'])
            ->where('reported_by', $accountId)
            ->orderByDesc('created_at')
            ->get();

        $riskMapReports = RiskArea::with(['marker', 'photos', 'barangayHall.barangay.city'])
            ->whereNotNull('marker_id')
            ->where(function ($query) use ($accountId) {
                $query->where('review_status', 'approved')
                    ->orWhere('reported_by', $accountId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($riskArea) => $riskArea->marker?->lat !== null && $riskArea->marker?->lng !== null)
            ->values();

        $userReports = $userIncidents->count() + $userRiskReports->count();
        $countReport = $userReports;
        $fireCount = $incidents
            ->filter(fn ($incident) => strtolower(trim((string) $incident->status)) === 'reported')
            ->count();
        $totalIncidentCount = max($incidents->count(), 1);
        $riskPercentage = round(($fireCount / $totalIncidentCount) * 100, 1);
        $riskLevel = $this->determineRiskLevel($riskPercentage);
        $weeklyActivity = $this->buildCitizenWeeklyActivity($userIncidents);
        $requestsCount = FsicRequest::query()->where('user_id', $accountId)->count();

        $nearbyIncidents = collect();

        if ($latitude !== null && $longitude !== null) {
            $nearbyIncidents = $incidents
                ->filter(function ($incident) use ($latitude, $longitude) {
                    if (! $incident->marker || $incident->marker->lat === null || $incident->marker->lng === null) {
                        return false;
                    }

                    $distance = $this->calculateDistanceMeters(
                        $latitude,
                        $longitude,
                        (float) $incident->marker->lat,
                        (float) $incident->marker->lng
                    );

                    $incident->computed_distance_meters = $distance;

                    return $distance <= self::CITIZEN_NEARBY_INCIDENT_RADIUS_METERS;
                })
                ->sortBy([
                    ['computed_distance_meters', 'asc'],
                    ['created_at', 'desc'],
                ])
                ->values();
        }

        $activeNearbyIncidents = $nearbyIncidents->filter(function ($incident) {
            $status = strtolower(trim($incident->status ?? ''));

            return in_array($status, [
                'reported',
                'responding',
                'en-route',
                'en route',
                'on scene',
                'on-scene',
            ], true);
        })->values();

        // A citizen can always see their own reports. Reports from other
        // citizens are exposed only while active and inside the nearby radius.
        $visibleIncidents = $userIncidents
            ->concat($activeNearbyIncidents)
            ->unique(fn ($incident) => (int) $incident->incident_id)
            ->sortByDesc('created_at')
            ->values();

        return [
            'summary' => [
                'active_near_you' => $activeNearbyIncidents->count(),
                'your_reports' => $userReports,
                'total_reports' => $countReport,
                'active_alerts' => $activeNearbyIncidents->count(),
                'risk_percentage' => $riskPercentage,
                'risk_level' => $riskLevel,
            ],
            'incidents' => $visibleIncidents->map(fn ($incident) => $this->serializeCitizenIncident($incident))->values(),
            'user_incidents' => $userIncidents->map(fn ($incident) => $this->serializeCitizenIncident($incident))->values(),
            'user_reports' => $userIncidents
                ->map(fn ($incident) => $this->serializeCitizenIncident($incident))
                ->concat($userRiskReports->map(fn ($riskArea) => $this->serializeCitizenRiskReport($riskArea)))
                ->sortByDesc('created_at')
                ->values(),
            'risk_map_reports' => $riskMapReports
                ->map(fn ($riskArea) => $this->serializeCitizenRiskMapRecord($riskArea))
                ->values(),
            'nearby_incidents' => $activeNearbyIncidents
                ->map(fn ($incident) => $this->serializeCitizenIncident($incident, $incident->computed_distance_meters ?? null))
                ->values(),
            'weekly_activity' => $weeklyActivity,
            'requests_count' => $requestsCount,
        ];
    }

    private function buildCitizenInitialMarkers(User $viewer)
    {
        $ownIncidentMarkerIds = Incident::query()
            ->where('reported_by', $viewer->user_id)
            ->whereNotNull('marker_id')
            ->pluck('marker_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return \App\Support\MarkerFeedBuilder::buildForViewer($viewer)
            ->filter(function (array $marker) use ($ownIncidentMarkerIds) {
                $category = strtolower(trim((string) ($marker['category'] ?? '')));
                $isIncidentMarker = $category === 'fire' || ! empty($marker['incident_public']);

                return ! $isIncidentMarker
                    || in_array((int) ($marker['marker_id'] ?? 0), $ownIncidentMarkerIds, true);
            })
            ->values();
    }

    private function buildCitizenAddressOptions(?int $selectedCityId): array
    {
        $provinces = Cache::remember('citizen:province-options:v1', now()->addHours(6), function () {
            return Province::query()
                ->select(['province_id', 'province_name'])
                ->orderBy('province_name')
                ->get();
        });

        $cities = Cache::remember('citizen:city-options:v1', now()->addHours(6), function () {
            return City::query()
                ->select(['city_id', 'city_name'])
                ->orderBy('city_name')
                ->get();
        });

        $selectedBarangays = $selectedCityId
            ? Cache::remember("citizen:barangays:city:{$selectedCityId}:v1", now()->addHours(6), function () use ($selectedCityId) {
                return Barangay::query()
                    ->select(['barangay_id', 'barangay_name', 'city_id'])
                    ->where('city_id', $selectedCityId)
                    ->orderBy('barangay_name')
                    ->get();
            })
            : collect();

        $cities = $cities->map(function ($city) use ($selectedCityId, $selectedBarangays) {
            $cityOption = clone $city;
            $cityOption->setRelation(
                'barangays',
                ((int) $cityOption->city_id === (int) $selectedCityId) ? $selectedBarangays : collect()
            );

            return $cityOption;
        });

        return [$provinces, $cities];
    }

    // note dapat kapag mag papass sa isang view kung lahat ng data kailangan mo, idaan mo nalang sa isang controller
    public function index(?string $section = null)
    {
        $accountId = Auth::user()->user_id;
        $initialSection = in_array($section, self::CITIZEN_DASHBOARD_SECTIONS, true) ? $section : 'overview';

        $citizenUser = User::with(['role', 'address.barangay.city'])->findOrFail($accountId);
        $incidents = Incident::with(['marker', 'station', 'barangay.city'])
            ->orderByDesc('created_at')
            ->get();
        $myReports = $incidents
            ->where('reported_by', $accountId)
            ->values();
        $userReports = $myReports->count();
        $countReport = $userReports;
        $userinfo = collect([$citizenUser]);
        $usermarker = $this->buildCitizenInitialMarkers($citizenUser);
        [$provinces, $cities] = $this->buildCitizenAddressOptions($citizenUser->address?->barangay?->city_id);

        $allStationOfficer = StationOfficer::query()
            ->select(['station_officer_id', 'station_id', 'user_id', 'rank', 'status'])
            ->with(['user:user_id,first_name,middle_name,last_name,email'])
            ->get();

        $stations = Cache::remember('citizen:dashboard:stations:v1', now()->addSeconds(45), function () {
            return Station::with(['marker', 'address.barangay.city'])->get();
        });

        $dashboardData = $this->buildCitizenDashboardData($accountId, null, null, $incidents, $myReports);
        $incidents = $myReports;
        $riskPercentage = $dashboardData['summary']['risk_percentage'];
        $riskLevel = $dashboardData['summary']['risk_level'];
        $activeAlerts = $dashboardData['summary']['active_alerts'];
        $totalAlerts = $activeAlerts;
        $nearbyIncidents = collect($dashboardData['nearby_incidents']);
        $overviewReportCount = $dashboardData['summary']['total_reports'];
        $citizenCombinedReports = $dashboardData['user_reports'];
        $riskMapReports = $dashboardData['risk_map_reports'];
        $citizenCommunityActivityRequests = CommunityActivityRequest::isAvailable()
            ? CommunityActivityRequest::query()
                ->with(['station:station_id,station_name', 'barangay:barangay_id,barangay_name', 'convertedEvent:event_id,event,event_date,schedule'])
                ->where('requester_user_id', $accountId)
                ->latest()
                ->limit(6)
                ->get()
            : collect();

        // request
        $fsicApplications = collect();
        $fsicRequests = FsicRequest::query()->where('user_id', $accountId)->latest()->get();

        return view('citizen.dashboard', compact('usermarker', 'myReports', 'countReport', 'stations', 'fsicApplications', 'fsicRequests',
            'provinces', 'cities', 'citizenUser', 'allStationOfficer', 'accountId', 'incidents', 'userReports', 'userinfo', 'riskPercentage', 'riskLevel', 'activeAlerts', 'totalAlerts', 'nearbyIncidents', 'initialSection', 'overviewReportCount', 'citizenCombinedReports', 'riskMapReports', 'citizenCommunityActivityRequests'));
        // return view('citizen.dashboard', compact('usermarker'));

    }

    public function dashboardData(Request $request)
    {
        $validated = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:lat'],
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
            'displayName' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'Citizen',
        ]);
    }

    public function storeCitizenRiskReport(Request $request, BoundaryMatcherService $boundaryMatcherService)
    {
        $validated = $request->validate([
            'report_kind' => ['required', Rule::in(['hazard', 'risk', 'concern'])],
            'concern_type' => ['nullable', Rule::in([
                'electrical_issue',
                'unsafe_cooking_lpg',
                'blocked_hydrant_access',
                'combustible_clutter',
                'dense_fire_prone_area',
                'unsure',
            ])],
            'condition_detail' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:500'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'user_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'user_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'photos' => ['nullable', 'array', 'max:'.self::RISK_HAZARD_REPORTS_MAX_FILES],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $photoValidationError = $this->validateRiskHazardPhotoUpload($request);
        if ($photoValidationError) {
            return response()->json([
                'success' => false,
                'message' => $photoValidationError,
            ], 422);
        }

        $user = User::with('address.barangay.hall.barangay')->findOrFail(Auth::user()->user_id);
        $reportKind = $validated['report_kind'] === 'concern'
            ? 'hazard'
            : $validated['report_kind'];
        $template = $validated['report_kind'] === 'concern'
            ? $this->resolveCitizenConcernTemplate((string) ($validated['concern_type'] ?? 'unsure'))
            : $this->resolveCitizenQuickRiskTemplate($validated['report_kind']);
        $targetLat = (float) $validated['lat'];
        $targetLng = (float) $validated['lng'];
        $hall = $boundaryMatcherService->locateBarangayHall($targetLat, $targetLng);

        if (! $hall) {
            return response()->json([
                'success' => false,
                'message' => 'The selected risk or hazard point is outside a saved barangay boundary, or the matched barangay has no linked hall yet.',
            ], 422);
        }

        if ($reportKind === 'hazard') {
            $userLat = isset($validated['user_lat']) ? (float) $validated['user_lat'] : null;
            $userLng = isset($validated['user_lng']) ? (float) $validated['user_lng'] : null;
            if ($userLat === null || $userLng === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your current location is required to validate non-fire safety concerns.',
                ], 422);
            }

            $distanceFromCitizen = $this->calculateDistanceMeters($userLat, $userLng, $targetLat, $targetLng);

            if ($distanceFromCitizen > 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non-fire safety concerns must use your current location or a point within 100 meters of you.',
                ], 422);
            }

        }

        $riskArea = DB::transaction(function () use ($validated, $template, $hall, $user, $targetLat, $targetLng, $request) {
            $citizenDetail = ! empty($validated['condition_detail'])
                ? $this->formatCitizenLabel((string) $validated['condition_detail'], '')
                : null;
            $citizenNotes = trim((string) ($validated['notes'] ?? ''));
            $remarks = 'Citizen quick report submitted from the dashboard guided flow. Initial citizen selection is subject to BFP verification and may be reclassified during review.';
            $reviewNotes = $template['review_notes'];

            if ($citizenDetail) {
                $remarks .= ' Citizen detail: '.$citizenDetail.'.';
                $reviewNotes .= ' Citizen detail: '.$citizenDetail.'.';
            }

            if ($citizenNotes !== '') {
                $remarks .= ' Citizen note: '.$citizenNotes;
                $reviewNotes .= ' Citizen note: '.$citizenNotes;
            }

            $marker = Marker::create([
                'title' => $template['title'],
                'description' => $citizenNotes !== ''
                    ? $template['location_description'].' Citizen note: '.$citizenNotes
                    : $template['location_description'],
                'lat' => $targetLat,
                'lng' => $targetLng,
                'status' => 'active',
                'category' => $template['marker_category'],
            ]);

            $riskArea = RiskArea::create([
                'marker_id' => $marker->marker_id,
                'barangay_hall_id' => $hall->barangay_hall_id,
                'station_id' => $this->resolveRiskAreaStationId($hall),
                'entry_type' => $template['entry_type'],
                'hazard_category' => $template['hazard_category'],
                'risk_type' => $template['risk_type'],
                'street_or_sitio' => $user->address?->street,
                'risk_factor' => $template['risk_factor'],
                'mode' => $template['mode'],
                'condition_code' => $template['condition_code'],
                'condition_label' => $template['condition_label'],
                'hazard_type' => $template['hazard_type'],
                'remarks' => $remarks,
                'status' => 'pending',
                'review_status' => 'pending',
                'review_type' => 'create',
                'review_notes' => $reviewNotes,
                'reported_by' => $user->user_id,
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

            return $riskArea;
        });

        $riskArea->loadMissing(['marker', 'barangayHall.barangay', 'photos']);
        Cache::forget('map:markers:index:v2');
        Cache::forget('map:markers:index:v3_public');
        RiskAreaReviewController::flushRiskReviewCache();
        RiskAreaFollowUpDispatcher::dispatchFor($riskArea);
        UserActivityLogger::log('submitted citizen '.$validated['report_kind'].' report', $user->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => $validated['report_kind'] === 'concern'
                ? 'Risk/Hazard report submitted for BFP verification.'
                : 'Risk/Hazard report submitted for BFP verification.',
            'data' => [
                'report' => $this->serializeCitizenRiskReport($riskArea),
            ],
        ]);
    }

    private function validateRiskHazardPhotoUpload(Request $request): ?string
    {
        $photos = collect($request->file('photos', []))
            ->filter()
            ->values();

        if ($photos->isEmpty()) {
            return null;
        }

        if ($photos->count() > self::RISK_HAZARD_REPORTS_MAX_FILES) {
            return 'You may upload up to 3 photos only.';
        }

        $totalBytes = $photos->sum(fn ($photo) => (int) $photo->getSize());

        if ($totalBytes > self::RISK_HAZARD_REPORTS_MAX_TOTAL_BYTES) {
            return 'The selected photos exceed the 5 MB total upload limit.';
        }

        return null;
    }

    private function resolveRiskAreaStationId(?BarangayHall $hall): int
    {
        $barangayId = (int) ($hall?->barangay_id ?? 0);

        if ($barangayId) {
            $coveredStationId = DB::table('station_barangay_coverage')
                ->where('barangay_id', $barangayId)
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN LOWER(COALESCE(coverage_type, '')) = 'primary' THEN 0 ELSE 1 END")
                ->orderBy('station_barangay_coverage_id')
                ->value('station_id');

            if ($coveredStationId) {
                return (int) $coveredStationId;
            }

            $cityId = (int) ($hall?->barangay?->city_id ?? 0);

            if ($cityId) {
                $cityStationId = Station::query()
                    ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
                    ->leftJoin('barangays as station_barangays', 'full_addresses.barangay_id', '=', 'station_barangays.barangay_id')
                    ->where('station_barangays.city_id', $cityId)
                    ->orderBy('stations.station_name')
                    ->value('stations.station_id');

                if ($cityStationId) {
                    return (int) $cityStationId;
                }
            }
        }

        return 1;
    }

    public function updateProfile(Request $request, ProfileImageService $profileImages)
    {
        $user = User::with('address')->findOrFail(Auth::id());

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+(?:\s[A-Za-z]+)*$/'],
            'middle_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z]+(?:\s[A-Za-z]+)*$/'],
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+(?:\s[A-Za-z]+)*$/'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'contact_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'city_id' => ['required', 'exists:cities,city_id'],
            'barangay_id' => [
                'required',
                Rule::exists('barangays', 'barangay_id')->where(function ($query) use ($request) {
                    $query->where('city_id', $request->city_id);
                }),
            ],
            'street' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9\s,\.-]+$/'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'first_name.regex' => 'First name must contain letters only.',
            'middle_name.regex' => 'Middle name must contain letters only.',
            'last_name.regex' => 'Last name must contain letters only.',
            'contact_number.regex' => 'Phone number must be 11 digits and start with 09.',
            'street.regex' => 'Street address may only contain letters, numbers, spaces, commas, periods, and hyphens.',
            'profile_image.image' => 'Profile photo must be a valid image file.',
            'profile_image.mimes' => 'Profile photo must be a JPG, PNG, or WEBP image.',
            'profile_image.max' => 'Profile photo must not exceed 5 MB.',
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
            $addressPayload = [
                'street' => $validated['street'],
                'barangay_id' => $validated['barangay_id'],
            ];

            if (Schema::hasColumn('full_addresses', 'postal_id')) {
                $addressPayload['postal_id'] = null;
            }

            $address = FullAddress::create($addressPayload);

            $user->address_id = $address->address_id;
        }

        if ($request->hasFile('profile_image')) {
            $user = $profileImages->replace($user, $request->file('profile_image'));
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
                    'full_name' => trim($user->first_name.' '.$user->last_name),
                    'initials' => strtoupper(substr($user->first_name ?: 'C', 0, 1).substr(($user->last_name ?: $user->first_name ?: 'P'), 0, 1)),
                    'role' => $user->role->role ?? 'Citizen',
                    'street' => $user->address?->street,
                    'barangay' => $user->address?->barangay?->barangay_name,
                    'barangay_id' => $user->address?->barangay?->barangay_id,
                    'city' => $user->address?->barangay?->city?->city_name,
                    'city_id' => $user->address?->barangay?->city?->city_id,
                    'image' => $user->image,
                    'image_url' => MediaPath::url($user->image),
                ],
            ]);
        }

        return redirect()
            ->route('citizen.dashboard', ['section' => 'profile'])
            ->with('success', 'Profile updated successfully.')
            ->with('open_section', 'profile');
    }

    public function updateProfilePhoto(Request $request, ProfileImageService $profileImages)
    {
        $user = User::with(['role', 'address.barangay.city'])->findOrFail(Auth::id());

        $validated = $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'profile_image.required' => 'Please select a profile photo first.',
            'profile_image.image' => 'Profile photo must be a valid image file.',
            'profile_image.mimes' => 'Profile photo must be a JPG, PNG, or WEBP image.',
            'profile_image.max' => 'Profile photo must not exceed 5 MB.',
        ]);

        $user = $profileImages->replace($user, $validated['profile_image']);

        UserActivityLogger::log('updated profile photo', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'user' => [
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'contact_number' => $user->contact_number,
                'full_name' => trim($user->first_name.' '.$user->last_name),
                'initials' => strtoupper(substr($user->first_name ?: 'C', 0, 1).substr(($user->last_name ?: $user->first_name ?: 'P'), 0, 1)),
                'role' => $user->role->role ?? 'Citizen',
                'street' => $user->address?->street,
                'barangay' => $user->address?->barangay?->barangay_name,
                'barangay_id' => $user->address?->barangay?->barangay_id,
                'city' => $user->address?->barangay?->city?->city_name,
                'city_id' => $user->address?->barangay?->city?->city_id,
                'image' => $user->image,
                'image_url' => $user->profile_image_url,
                'thumbnail_url' => $user->profile_thumbnail_url,
            ],
        ]);
    }

    public function updateProfileEmail(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        if ($user->email === $validated['email']) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a different email address.',
                'errors' => [
                    'email' => ['Please enter a different email address.'],
                ],
            ], 422);
        }

        $user->email = $validated['email'];
        $user->save();

        UserActivityLogger::log('updated email address', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Email address updated successfully.',
            'user' => [
                'email' => $user->email,
            ],
        ]);
    }

    public function updateProfilePassword(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => [
                    'current_password' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        if (Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Please choose a password different from your current password.',
                'errors' => [
                    'password' => ['Please choose a password different from your current password.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        UserActivityLogger::log('updated password', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }

    public function homepage()
    {
        $anonymousUserId = null;
        $totalStations = 0;
        $totalHydrants = 0;
        $totalIncidents = 0;
        $latestRecordAt = null;
        $landingSlides = collect();

        try {
            $anonymousUserId = User::resolveAnonymousUserId();
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            if (Schema::hasTable('landing_media')) {
                $landingSlides = LandingMedia::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            $totalStations = Marker::query()->where('category', 'station')->count();
            $totalHydrants = Marker::query()->where('category', 'hydrant')->count();
            $totalIncidents = Incident::query()->count();

            $latestRecordAt = collect([
                Marker::query()->max('updated_at'),
                Incident::query()->max('updated_at'),
                $landingSlides->max('updated_at'),
            ])->filter()->max();
        } catch (\Throwable $exception) {
            report($exception);
        }

        return view('dashboard', compact(
            'anonymousUserId',
            'totalStations',
            'totalHydrants',
            'totalIncidents',
            'latestRecordAt',
            'landingSlides',
        ));
    }
    //     public function dashboard()
    // {
    //     $accountId = Auth::user()->account->account_id;

    //     return view('citizen.dashboard', compact('accountId'));
    // }
}
