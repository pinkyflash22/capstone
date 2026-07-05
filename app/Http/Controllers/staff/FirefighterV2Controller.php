<?php

namespace App\Http\Controllers\staff;

use App\Models\Equipment;
use App\Models\EquipmentToStation;
use App\Models\EventActivity;
use App\Models\FinalInvestigationReport;
use App\Models\FireOrigin;
use App\Models\FsicRequest;
use App\Models\Incident;
use App\Models\Post;
use App\Models\ProgressInvestigationReport;
use App\Models\Province;
use App\Models\SpotInvestigationReport;
use App\Models\Station;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\User;
use App\Services\BoundaryMatcherService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FirefighterV2Controller extends FirefighterController
{
    public function dashboard()
    {
        $userId = Auth::user()->user_id;
        $stationOfficer = $this->getStationOfficerForUserId($userId);
        $userTeam = $this->resolveUserTeam($userId);

        return view('firefighters.v2-dashboard', [
            'stationOfficer' => $stationOfficer,
            'userTeam' => $userTeam,
        ]);
    }

    public function incidents()
    {
        return view('firefighters.v2-incidents', [
            'stationOfficer' => $this->getStationOfficerForUserId(Auth::user()->user_id),
            'userTeam' => $this->resolveUserTeam(Auth::user()->user_id),
        ]);
    }

    public function dashboardData()
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildDashboardPayload(Auth::user()->user_id),
        ]);
    }

    public function incidentsData()
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildFirefighterIncidentRegistryData(Auth::user()->user_id),
        ]);
    }

    public function station(BoundaryMatcherService $boundaryMatcherService)
    {
        $userId = Auth::user()->user_id;
        $user = User::with(['role', 'address.barangay.city'])->where('user_id', $userId)->firstOrFail();
        $stationOfficer = StationOfficer::with([
            'user.role',
            'station.marker',
            'station.profileMedia',
            'station.galleryMedia',
            'station.address.barangay.city',
        ])->where('user_id', $userId)->firstOrFail();

        $station = $stationOfficer->station;
        $stationId = $stationOfficer->station_id;
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)->pluck('station_officer_id');
        $stationTeamIds = Team::query()
            ->where(function ($query) use ($stationOfficerIds) {
                foreach ([
                    'commander_driver',
                    'pumper',
                    'team_leader',
                    'firefighter_officer_1',
                    'firefighter_officer_2',
                    'firefighter_officer_3',
                    'rescue_radio',
                ] as $column) {
                    $query->orWhereIn($column, $stationOfficerIds);
                }
            })
            ->pluck('team_id');

        $stationOfficers = StationOfficer::with(['user.role'])
            ->where('station_id', $stationId)
            ->orderBy('rank')
            ->orderBy('station_officer_id')
            ->get();

        $userTeam = $this->resolveUserTeam($userId);
        $crewMembers = $this->buildCrewMembers($userTeam);

        $equipmentAssignments = EquipmentToStation::with(['equipment.category'])
            ->where('station_id', $stationId)
            ->orderBy('equipment_station_id')
            ->get();

        $spotReports = SpotInvestigationReport::with(['incident.marker', 'investigator.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(8, ['*'], 'spot_page')
            ->withQueryString();

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'investigator.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(8, ['*'], 'progress_page')
            ->withQueryString();

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(8, ['*'], 'final_page')
            ->withQueryString();

        $availableSpotIncidents = Incident::with(['marker', 'barangay.city'])
            ->where(function ($query) {
                $query->whereIn('status', [
                    self::STATUS_CONTROLLED,
                    'controlled',
                    'Controlled',
                    'completed',
                    'Completed',
                ]);
            })
            ->whereIn('team_id', $stationTeamIds)
            ->whereNotIn('incident_id', function ($query) {
                $query->select('incident_id')->from('spot_investigation_report');
            })
            ->latest()
            ->get()
            ->map(function ($incident) use ($boundaryMatcherService) {
                $resolvedBarangayName = $incident->barangay?->barangay_name;
                $resolvedCityName = $incident->barangay?->city?->city_name;

                if ((! $resolvedBarangayName || ! $resolvedCityName) && $incident->marker?->lat !== null && $incident->marker?->lng !== null) {
                    $matchedBoundary = $boundaryMatcherService->locateBarangayBoundary(
                        (float) $incident->marker->lat,
                        (float) $incident->marker->lng
                    );

                    $resolvedBarangayName = $resolvedBarangayName ?: $matchedBoundary?->barangay?->barangay_name;
                    $resolvedCityName = $resolvedCityName ?: $matchedBoundary?->barangay?->city?->city_name;
                }

                $incident->resolved_dropdown_barangay_name = $resolvedBarangayName;
                $incident->resolved_dropdown_city_name = $resolvedCityName;

                return $incident;
            });

        $fireOrigins = FireOrigin::orderBy('fire_origin_id')->get();
        $provinces = Province::all();

        return view('firefighters.v2-station', compact(
            'availableSpotIncidents',
            'crewMembers',
            'equipmentAssignments',
            'finalReports',
            'fireOrigins',
            'progressReports',
            'provinces',
            'spotReports',
            'station',
            'stationOfficer',
            'stationOfficers',
            'user',
            'userTeam'
        ));
    }

    public function requests()
    {
        $currentUserId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::with('station.marker')
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        $assignedFsicRequests = FsicRequest::with(['user', 'inspection.checklist', 'approval'])
            ->whereHas('inspection', function ($query) use ($stationOfficer) {
                $query->where('station_officer_id', $stationOfficer->station_officer_id);
            })
            ->whereIn('status', ['for_inspection', 'inspected', 'approved'])
            ->orderByRaw("CASE status WHEN 'for_inspection' THEN 1 WHEN 'inspected' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END")
            ->orderBy('date_requested', 'asc')
            ->get();

        return view('firefighters.v2-requests', compact('assignedFsicRequests', 'stationOfficer'));
    }

    public function scheduleV2()
    {
        $userId = Auth::user()->user_id;
        $userinfo = User::with(['role', 'address.barangay.city'])
            ->where('user_id', $userId)
            ->get();

        $stationOfficer = $this->getStationOfficerForUserId($userId);
        $stationId = $stationOfficer?->station_id;
        $stationName = $stationOfficer?->station?->station_name;

        $scheduleEvents = EventActivity::query()
            ->where(function ($query) use ($stationId, $userId) {
                if ($stationId) {
                    $query->where(function ($stationQuery) use ($stationId) {
                        $stationQuery->where('station_id', $stationId)
                            ->whereNull('user_id');
                    });
                }

                $query->orWhere('user_id', $userId);
            })
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (EventActivity $event) => $this->serializeScheduleEvent($event, $userId))
            ->values();

        return view('firefighters.v2-schedule', compact(
            'scheduleEvents',
            'stationId',
            'stationName',
            'stationOfficer',
            'userinfo'
        ));
    }

    public function announcements()
    {
        $userinfo = User::with(['role', 'address.barangay.city'])
            ->where('user_id', Auth::user()->user_id)
            ->get();

        $recentPosts = Post::with('user')->latest()->take(8)->get();
        $postStats = [
            'total_posts' => Post::count(),
            'recent_posts' => Post::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('firefighters.v2-announcements', compact('postStats', 'recentPosts', 'userinfo'));
    }

    public function profile()
    {
        $user = User::with(['role', 'address.barangay.city'])
            ->where('user_id', Auth::user()->user_id)
            ->firstOrFail();

        $stationOfficer = StationOfficer::with(['station.address.barangay.city'])
            ->where('user_id', $user->user_id)
            ->first();

        $userTeam = $this->resolveUserTeam($user->user_id);
        $registryData = $this->buildFirefighterIncidentRegistryData($user->user_id);

        $profileStats = [
            'active_incidents' => $registryData['stats']['active'] ?? 0,
            'total_incidents' => $registryData['stats']['total'] ?? 0,
            'controlled_incidents' => $registryData['stats']['controlled'] ?? 0,
            'closed_incidents' => $registryData['stats']['closed'] ?? 0,
            'avg_response_minutes' => $registryData['stats']['avg_response_minutes'] ?? 0,
        ];

        return view('firefighters.v2-profile', compact('profileStats', 'stationOfficer', 'user', 'userTeam'));
    }

    protected function buildCrewMembers(?Team $userTeam): Collection
    {
        $teamMemberColumns = [
            'commander_driver' => 'Commander / Driver',
            'pumper' => 'Pumper',
            'team_leader' => 'Team Leader',
            'firefighter_officer_1' => 'Firefighter Officer 1',
            'firefighter_officer_2' => 'Firefighter Officer 2',
            'firefighter_officer_3' => 'Firefighter Officer 3',
            'rescue_radio' => 'Rescue Radio',
        ];

        $crewMembers = collect();

        if (! $userTeam) {
            return $crewMembers;
        }

        $teamOfficerIds = collect(array_keys($teamMemberColumns))
            ->map(fn ($column) => $userTeam->{$column})
            ->filter()
            ->values();

        $membersById = StationOfficer::with(['user.role'])
            ->whereIn('station_officer_id', $teamOfficerIds)
            ->get()
            ->keyBy('station_officer_id');

        foreach ($teamMemberColumns as $column => $label) {
            $memberOfficerId = $userTeam->{$column};
            if (! $memberOfficerId) {
                continue;
            }

            $member = $membersById->get($memberOfficerId);
            if (! $member?->user) {
                continue;
            }

            $crewMembers->push((object) [
                'user_id' => $member->user->user_id,
                'station_officer_id' => $member->station_officer_id,
                'name' => trim(($member->user->first_name ?? '') . ' ' . ($member->user->last_name ?? '')) ?: ('Officer #' . $memberOfficerId),
                'role_label' => $label,
                'role_name' => $member->user->role?->role,
            ]);
        }

        return $crewMembers;
    }

    protected function buildDashboardPayload(int $userId): array
    {
        return Cache::remember(
            "firefighter:v2:dashboard:{$userId}",
            now()->addSeconds(20),
            function () use ($userId) {
                $stationOfficer = $this->getStationOfficerForUserId($userId);
                $userTeam = $this->resolveUserTeam($userId);
                $incidentCoords = collect();

                if ($userTeam) {
                    $incidentCoords = $this->loadTeamIncidentsForRegistry((int) $userTeam->team_id, true);
                }

                $stations = Station::query()
                    ->select(['station_id', 'station_name', 'marker_id'])
                    ->with(['marker:marker_id,title,description,lat,lng,status,category,image'])
                    ->get();

                $posts = Post::query()
                    ->select(['id', 'user_id', 'title', 'content', 'description', 'created_at'])
                    ->with(['user:user_id,first_name,last_name'])
                    ->latest()
                    ->take(6)
                    ->get();

                $thisStation = $stationOfficer?->station_id;
                $thiscrew = $thisStation
                    ? StationOfficer::query()
                        ->select(['station_officer_id', 'station_id', 'user_id', 'rank'])
                        ->where('station_id', $thisStation)
                        ->with(['user:user_id,first_name,last_name,role_id', 'user.role:role_id,role'])
                        ->get()
                    : collect();

                $currentStation = $stations->firstWhere('station_id', $thisStation);
                $originLat = optional($currentStation?->marker)->lat;
                $originLng = optional($currentStation?->marker)->lng;

                if ($originLat !== null && $originLng !== null) {
                    $stations = $stations
                        ->filter(function ($station) use ($thisStation) {
                            return $station->station_id != $thisStation
                                && optional($station->marker)->lat !== null
                                && optional($station->marker)->lng !== null;
                        })
                        ->map(function ($station) use ($originLat, $originLng) {
                            $station->distance = $this->haversineDistance(
                                (float) $originLat,
                                (float) $originLng,
                                (float) $station->marker->lat,
                                (float) $station->marker->lng
                            );

                            return $station;
                        })
                        ->sortBy('distance')
                        ->take(5)
                        ->values();
                } else {
                    $stations = $stations
                        ->filter(fn ($station) => $station->station_id != $thisStation)
                        ->take(5)
                        ->values();
                }

                return [
                    'stats' => [
                        'active_incidents' => $incidentCoords->count(),
                        'crew_on_station' => $thiscrew->count(),
                        'nearby_stations' => $stations->count(),
                        'available_equipment' => Cache::remember('firefighter:v2:equipment-count', now()->addMinutes(5), fn () => Equipment::count()),
                    ],
                    'incidents' => $incidentCoords->map(function (Incident $incident) {
                        return [
                            'incident_id' => $incident->incident_id,
                            'status' => $this->getIncidentStatusLabel($incident->status),
                            'title' => $incident->marker?->title ?? ('Incident #' . $incident->incident_id),
                            'lat' => $incident->marker?->lat,
                            'lng' => $incident->marker?->lng,
                        ];
                    })->values(),
                    'stations' => $stations->map(function (Station $station) {
                        return [
                            'station_id' => $station->station_id,
                            'station_name' => $station->station_name,
                            'lat' => $station->marker?->lat,
                            'lng' => $station->marker?->lng,
                            'distance' => isset($station->distance) ? round((float) $station->distance, 2) : null,
                        ];
                    })->values(),
                    'crew' => $thiscrew->take(5)->map(function (StationOfficer $crew) {
                        return [
                            'name' => trim(($crew->user->first_name ?? '') . ' ' . ($crew->user->last_name ?? '')) ?: 'Station Officer',
                            'rank' => $crew->rank ?? 'Responder',
                            'role' => $crew->user->role->role ?? 'Firefighter',
                        ];
                    })->values(),
                    'posts' => $posts->map(function (Post $post) {
                        return [
                            'title' => $post->title ?? 'Station Update',
                            'summary' => \Illuminate\Support\Str::limit(strip_tags($post->content ?? $post->description ?? 'No summary available.'), 110),
                            'author' => trim(($post->user->first_name ?? '') . ' ' . ($post->user->last_name ?? '')) ?: 'BFP Staff',
                            'created_at_human' => optional($post->created_at)->diffForHumans(),
                        ];
                    })->values(),
                ];
            }
        );
    }
}
