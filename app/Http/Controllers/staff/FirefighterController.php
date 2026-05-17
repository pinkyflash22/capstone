<?php

namespace App\Http\Controllers\staff;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
// use App\Models\Coordinate;
use App\Models\Post;
use App\Models\Station;
use App\Models\Incident;
use App\Models\StationOfficer;
use App\Models\FsicRequest;
use App\Models\FsicInspection;
use App\Models\InspectionChecklist;
use App\Models\FireOrigin;
use App\Models\Province;
use App\Models\SpotInvestigationReport;
use App\Models\ProgressInvestigationReport;
use App\Models\FinalInvestigationReport;
use Illuminate\Support\Facades\Auth;



use App\Models\Team;


use App\Models\User;


use App\Models\Equipment;
use App\Models\EquipmentToStation;



use Illuminate\Http\Request;
use App\Support\UserActivityLogger;

class FirefighterController extends Controller
{
    private const STATUS_REPORTED = 'reported';
    private const STATUS_RESPONDING = 'en-route';
    private const STATUS_ON_SCENE = 'on-scene';
    private const STATUS_CONTROLLED = 'completed';
    private const STATUS_CLOSED = 'closed';
    private const STATUS_DB_REPORTED = 'Reported';
    private const STATUS_DB_RESPONDING = 'Responding';
    private const STATUS_DB_ON_SCENE = 'On Scene';
    private const STATUS_DB_CONTROLLED = 'Controlled';
    private const STATUS_DB_CLOSED = 'Closed';

    private function resolveUserLocationTable(): ?string
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('user_locations')) {
            return 'user_locations';
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('user_location')) {
            return 'user_location';
        }

        return null;
    }

    protected function getStationOfficerForUserId(?int $userId): ?StationOfficer
    {
        if (!$userId) {
            return null;
        }

        return StationOfficer::with('station')
            ->where('user_id', $userId)
            ->first();
    }

    protected function getIncidentStatusId(?string $status): int
    {
        return match (strtolower(trim((string) $status))) {
            self::STATUS_RESPONDING,
            'responding',
            'en route' => 2,
            self::STATUS_ON_SCENE,
            'on scene' => 3,
            self::STATUS_CONTROLLED,
            'controlled' => 4,
            self::STATUS_CLOSED => 5,
            default => 1,
        };
    }

    protected function getIncidentStatusLabel(?string $status): string
    {
        return match ($this->getIncidentStatusId($status)) {
            2 => 'Responding',
            3 => 'On Scene',
            4 => 'Controlled',
            5 => 'Closed',
            default => 'Reported',
        };
    }

    protected function getIncidentTypeLabel(?Incident $incident): string
    {
        return strtolower(trim((string) $incident?->marker?->category)) === 'fire'
            ? 'Fire'
            : 'Incident';
    }

    protected function normalizeStoredMediaPath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', trim($path));

        if ($normalizedPath === '') {
            return null;
        }

        return str_starts_with($normalizedPath, 'http://') || str_starts_with($normalizedPath, 'https://')
            ? $normalizedPath
            : asset('storage/' . ltrim($normalizedPath, '/'));
    }

    protected function resolveUserTeam(?int $userId): ?Team
    {
        if (!$userId) {
            return null;
        }

        $stationOfficerId = StationOfficer::where('user_id', $userId)->value('station_officer_id');

        if (!$stationOfficerId) {
            return null;
        }

        return Team::where('commander_driver', $stationOfficerId)
            ->orWhere('pumper', $stationOfficerId)
            ->orWhere('team_leader', $stationOfficerId)
            ->orWhere('firefighter_officer_1', $stationOfficerId)
            ->orWhere('firefighter_officer_2', $stationOfficerId)
            ->orWhere('firefighter_officer_3', $stationOfficerId)
            ->orWhere('rescue_radio', $stationOfficerId)
            ->first();
    }

    protected function buildFirefighterIncidentRegistryData(int $userId): array
    {
        $stationOfficer = $this->getStationOfficerForUserId($userId);

        $userTeam = $this->resolveUserTeam($userId);
        $incidents = collect();

        if ($userTeam) {
            $incidents = Incident::with([
                    'marker',
                    'reporter',
                    'team',
                    'route',
                ])
                ->where('team_id', $userTeam->team_id)
                ->orderByDesc('created_at')
                ->get();
        }

        $completedIncidents = $incidents->map(function ($incident) {
            $arrivalRoute = $incident->route
                ? $incident->route
                    ->filter(fn ($route) => !empty($route->updated_at))
                    ->sortByDesc('updated_at')
                    ->first()
                : null;

            if (!$incident->created_at || !$arrivalRoute?->updated_at) {
                return null;
            }

            return [
                'incident' => $incident,
                'response_minutes' => Carbon::parse($incident->created_at)
                    ->diffInMinutes(Carbon::parse($arrivalRoute->updated_at)),
                'arrival_at' => Carbon::parse($arrivalRoute->updated_at),
            ];
        })->filter();

        $averageResponseMinutes = $completedIncidents->count() > 0
            ? round((float) ($completedIncidents->sum('response_minutes') / $completedIncidents->count()), 1)
            : 0;

        $stats = [
            'total' => $incidents->count(),
            'active' => $incidents->filter(fn ($incident) => in_array($this->getIncidentStatusId($incident->status), [2, 3], true))->count(),
            'controlled' => $incidents->filter(fn ($incident) => $this->getIncidentStatusId($incident->status) === 4)->count(),
            'closed' => $incidents->filter(fn ($incident) => $this->getIncidentStatusId($incident->status) === 5)->count(),
            'avg_response_minutes' => $averageResponseMinutes,
        ];

        $mappedIncidents = $incidents->map(function ($incident) {
            $marker = $incident->marker;
            $arrivalRoute = $incident->route
                ? $incident->route
                    ->filter(fn ($route) => !empty($route->updated_at))
                    ->sortByDesc('updated_at')
                    ->first()
                : null;
            $arrivalAt = $arrivalRoute?->updated_at ? Carbon::parse($arrivalRoute->updated_at) : null;

            $reporterName = trim(($incident->reporter?->first_name ?? '') . ' ' . ($incident->reporter?->last_name ?? ''));
            $incidentStatusId = $this->getIncidentStatusId($incident->status);

            return [
                'incident_id' => $incident->incident_id,
                'incident_status_id' => $incidentStatusId,
                'incident_status_name' => $this->getIncidentStatusLabel($incident->status),
                'incident_type_id' => null,
                'incident_type_name' => $this->getIncidentTypeLabel($incident),
                'team_id' => $incident->team_id,
                'team_name' => $incident->team?->team_name ?? ('Team #' . $incident->team_id),
                'marker_id' => $incident->marker_id,
                'title' => $marker?->title ?? ('Incident #' . $incident->incident_id),
                'description' => $marker?->description,
                'location' => $marker?->title ?? 'Unnamed location',
                'coordinates' => [
                    'lat' => $marker?->lat,
                    'lng' => $marker?->lng,
                ],
                'reported_at' => optional($incident->created_at)->toIso8601String(),
                'updated_at' => optional($incident->updated_at)->toIso8601String(),
                'created_at' => optional($incident->created_at)->toIso8601String(),
                'arrival_at' => $arrivalAt?->toIso8601String(),
                'resolved_at' => in_array($incidentStatusId, [4, 5], true)
                    ? optional($incident->updated_at)->toIso8601String()
                    : null,
                'response_minutes' => $incident->created_at && $arrivalAt
                    ? Carbon::parse($incident->created_at)->diffInMinutes($arrivalAt)
                    : null,
                'reporter_name' => $reporterName !== '' ? $reporterName : 'Concerned Citizen',
                'status' => $incident->status,
                'marker' => $marker,
                'image_url' => $this->normalizeStoredMediaPath($marker?->image),
            ];
        })->values();

        return [
            'station_officer' => $stationOfficer ? [
                'station_officer_id' => $stationOfficer->station_officer_id,
                'station_name' => $stationOfficer->station?->station_name,
                'rank' => $stationOfficer->rank,
            ] : null,
            'team' => $userTeam ? [
                'team_id' => $userTeam->team_id,
                'team_name' => $userTeam->team_name ?? ('Team #' . $userTeam->team_id),
            ] : null,
            'stats' => $stats,
            'incidents' => $mappedIncidents,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $ids = $user->user_id;
        $userinfo = User::where('user_id', $ids)->with('role', 'address', 'address.barangay', 'address.barangay.city')->get();
        $stationOfficer = $this->getStationOfficerForUserId($ids);
        $userTeam = $this->resolveUserTeam($ids);
        $incidentCoords = collect();

        if ($userTeam) {
            $incidentCoords = Incident::with(['marker', 'team', 'reporter'])
                ->where('team_id', $userTeam->team_id)
                ->whereNotIn('status', [self::STATUS_DB_CONTROLLED, self::STATUS_DB_CLOSED])
                ->orderByDesc('created_at')
                ->get();
        }

        $incidents = $incidentCoords;
        
        // Get all markers for the map (stations, incidents, etc.)
        $allMarkers = \App\Models\Marker::all();
        
        // Get stations
        $stations = \App\Models\Station::with('marker')->get();
        
        // Get announcements
        $posts = \App\Models\Post::with('user')->latest()->take(5)->get();
        // $incidentCoords = Coordinate::with(['marker', 'image'])
        //             ->latest()
        //             // ->take(2)
        //             ->get();
        // ->get();
        
        $thisStation = $stationOfficer?->station_id;

        //all station officer per station
        $thiscrew = $thisStation
            ? StationOfficer::where('station_id', $thisStation)->with(['user.role'])->get()
            : collect();

        // Keep only the 5 nearest stations from the logged-in firefighter station.
        $currentStation = $stations->firstWhere('station_id', $thisStation);
        $originLat = optional($currentStation?->marker)->lat;
        $originLng = optional($currentStation?->marker)->lng;

        if ($originLat !== null && $originLng !== null) {
            $stations = $stations
                ->filter(function ($station) use ($thisStation) {
                    return $station->station_id != $thisStation &&
                        optional($station->marker)->lat !== null &&
                        optional($station->marker)->lng !== null;
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
                ->filter(fn($station) => $station->station_id != $thisStation)
                ->take(5)
                ->values();
        }

        $equipments = Equipment::all();
        return view('firefighters.dashboard', compact('incidentCoords', 'thiscrew', 'posts', 'stations', 'equipments', 'allMarkers', 'userTeam', 'userinfo', 'incidents', 'thisStation'));
    }

    public function equipments(){
        $currentUserId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::with('station.marker')
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        $assignedFsicRequests = FsicRequest::with([
                'user',
                'inspection.checklist',
                'approval',
            ])
            ->whereHas('inspection', function ($query) use ($stationOfficer) {
                $query->where('station_officer_id', $stationOfficer->station_officer_id);
            })
            ->whereIn('status', ['for_inspection', 'inspected', 'approved'])
            ->orderByRaw("CASE status WHEN 'for_inspection' THEN 1 WHEN 'inspected' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END")
            ->orderBy('date_requested', 'asc')
            ->get();

        return view('firefighters.equipments', [
            'stationOfficer' => $stationOfficer,
            'assignedFsicRequests' => $assignedFsicRequests,
        ]);
    }

    public function getAssignedFsicRequest(string $id)
    {
        $stationOfficer = StationOfficer::where('user_id', Auth::user()->user_id)->firstOrFail();

        $fsicRequest = FsicRequest::with(['user', 'inspection.checklist', 'approval'])
            ->whereHas('inspection', function ($query) use ($stationOfficer) {
                $query->where('station_officer_id', $stationOfficer->station_officer_id);
            })
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $fsicRequest,
        ]);
    }

    public function submitFsicInspection(Request $request, string $id)
    {
        $stationOfficer = StationOfficer::where('user_id', Auth::user()->user_id)->firstOrFail();

        $fsicRequest = FsicRequest::with('inspection.checklist')
            ->whereHas('inspection', function ($query) use ($stationOfficer) {
                $query->where('station_officer_id', $stationOfficer->station_officer_id);
            })
            ->findOrFail($id);

        $inspectionAlreadyCompleted = $fsicRequest->inspection
            && ($fsicRequest->inspection->inspection_date || $fsicRequest->inspection->checklist);

        if ($fsicRequest->status === 'approved' || $inspectionAlreadyCompleted) {
            return response()->json([
                'success' => false,
                'message' => 'This FSIC request has already been inspected.',
            ], 422);
        }

        $validated = $request->validate([
            'inspection_remarks' => 'required|string|max:1000',
            'checklist.fire_protection' => 'required|boolean',
            'checklist.alarm_systems' => 'required|boolean',
            'checklist.standard_material_used' => 'required|boolean',
            'checklist.electrical' => 'required|boolean',
            'checklist.housekeeping' => 'required|boolean',
            'checklist.hazards' => 'required|boolean',
            'checklist.planning' => 'required|boolean',
            'checklist.remarks' => 'nullable|string|max:1000',
        ]);

        $checklistData = $validated['checklist'];
        $isCompliant = collect([
            $checklistData['fire_protection'],
            $checklistData['alarm_systems'],
            $checklistData['standard_material_used'],
            $checklistData['electrical'],
            $checklistData['housekeeping'],
            $checklistData['hazards'],
            $checklistData['planning'],
        ])->every(fn ($value) => (int) $value === 1);

        $inspection = FsicInspection::updateOrCreate(
            ['fsic_request_id' => $fsicRequest->id],
            [
                'station_officer_id' => $stationOfficer->station_officer_id,
                'inspection_date' => now(),
                'inspection_remarks' => $validated['inspection_remarks'],
                'is_compliant' => $isCompliant ? 1 : 0,
            ]
        );

        InspectionChecklist::updateOrCreate(
            ['fsic_inspection_id' => $inspection->id],
            [
                'fire_protection' => (int) $checklistData['fire_protection'],
                'alarm_systems' => (int) $checklistData['alarm_systems'],
                'standard_material_used' => (int) $checklistData['standard_material_used'],
                'electrical' => (int) $checklistData['electrical'],
                'housekeeping' => (int) $checklistData['housekeeping'],
                'hazards' => (int) $checklistData['hazards'],
                'planning' => (int) $checklistData['planning'],
                'remarks' => $checklistData['remarks'] ?? null,
            ]
        );

        $fsicRequest->status = 'inspected';
        $fsicRequest->save();
        UserActivityLogger::log('submitted fsic inspect', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'FSIC inspection submitted successfully.',
            'data' => $fsicRequest->fresh(['user', 'inspection.checklist', 'approval']),
        ]);
    }

    public function myStation(){
        $userId = Auth::user()->user_id;
        $user = User::with(['role', 'address.barangay.city'])->where('user_id', $userId)->firstOrFail();
        $stationOfficer = StationOfficer::with([
                'user.role',
                'station.marker',
                'station.address.barangay.city',
            ])
            ->where('user_id', $userId)
            ->firstOrFail();

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
        if ($userTeam) {
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
                if (!$memberOfficerId) {
                    continue;
                }

                $member = $membersById->get($memberOfficerId);
                if (!$member?->user) {
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
        }

        $equipmentAssignments = EquipmentToStation::with(['equipment.category'])
            ->where('station_id', $stationId)
            ->orderBy('equipment_station_id')
            ->get();

        $spotReports = SpotInvestigationReport::with(['incident.marker', 'investigator.user', 'progressInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'fireOrigin', 'investigator.user', 'finalInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.fireOrigin'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $availableSpotIncidents = Incident::with(['marker'])
            ->where('status', self::STATUS_CONTROLLED)
            ->whereIn('team_id', $stationTeamIds)
            ->whereNotIn('incident_id', function ($query) {
                $query->select('incident_id')->from('spot_investigation_report');
            })
            ->latest()
            ->get();

        $availableSpotReports = SpotInvestigationReport::with('incident.marker')
            ->whereDoesntHave('progressInvestigation')
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $availableProgressReports = ProgressInvestigationReport::with('spotInvestigation.incident.marker')
            ->whereDoesntHave('finalInvestigation')
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $fireOrigins = FireOrigin::orderBy('fire_origin_id')->get();
        $provinces = Province::all();

        return view('firefighters.mystation', compact(
            'user',
            'stationOfficer',
            'station',
            'stationOfficers',
            'userTeam',
            'crewMembers',
            'equipmentAssignments',
            'spotReports',
            'progressReports',
            'finalReports',
            'availableSpotIncidents',
            'availableSpotReports',
            'availableProgressReports',
            'fireOrigins',
            'provinces'
        ));
    }

    public function schedule()
{
    $userinfo = User::with(['role', 'address.barangay.city'])->where('user_id', Auth::user()->user_id)->get();
    return view('firefighters.schedule', compact('userinfo'));
}

public function certifications()
{
    $userinfo = User::with(['role', 'address.barangay.city'])->where('user_id', Auth::user()->user_id)->get();
    return view('firefighters.certifications', compact('userinfo'));
}

public function training()
{
    $userinfo = User::with(['role', 'address.barangay.city'])->where('user_id', Auth::user()->user_id)->get();
    return view('firefighters.training', compact('userinfo'));
}

public function myProfile()
{
    $user = User::with(['role', 'address.barangay.city'])
        ->where('user_id', Auth::user()->user_id)
        ->firstOrFail();

    $stationOfficer = StationOfficer::with(['station.address.barangay.city'])
        ->where('user_id', $user->user_id)
        ->first();

    return view('firefighters.my-profile', compact('user', 'stationOfficer'));
}

public function updateMyProfile(Request $request)
{
    $user = User::where('user_id', Auth::user()->user_id)->firstOrFail();

    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'middle_name' => ['nullable', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->user_id . ',user_id'],
        'contact_number' => ['required', 'string', 'max:30'],
        'password' => ['nullable', 'string', 'min:8', 'confirmed'],
    ]);

    if (blank($validated['password'] ?? null)) {
        unset($validated['password']);
    }

    unset($validated['password_confirmation']);

    $user->update($validated);
    UserActivityLogger::log('updated profile', Auth::user()?->user_id, $request);

    return redirect()
        ->route('firefighter.my-profile')
        ->with('success', 'Profile updated successfully.');
}
    public function getAllLocations()
    {
        try {
            $userLocationTable = $this->resolveUserLocationTable();

            if (!$userLocationTable) {
                return response()->json([
                    'success' => false,
                    'message' => 'user location table does not exist yet',
                    'locations' => []
                ]);
            }
            // Get all online/active firefighters with their last known locations
            
            $locations = DB::table($userLocationTable)
                ->join('users', $userLocationTable . '.user_id', '=', 'users.user_id')
                ->where($userLocationTable . '.updated_at', '>=', Carbon::now()->subMinutes(5))
                ->select($userLocationTable . '.*', 'users.first_name', 'users.last_name')
                ->get();
            
            return response()->json([
                'success' => true,
                'locations' => $locations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
}

public function updateLocation(Request $request)
{
    try {
        $userLocationTable = $this->resolveUserLocationTable();

        if (!$userLocationTable) {
            return response()->json([
                'success' => false,
                'message' => 'user location table does not exist yet'
            ], 500);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $resolvedUserId = Auth::user()?->user_id ?? $validated['user_id'] ?? null;

        if (! $resolvedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve the current user for location tracking.'
            ], 422);
        }

        DB::table($userLocationTable)->updateOrInsert(
            ['user_id' => $resolvedUserId],
            [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'updated_at' => now(),
                'created_at' => now()
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Location updated'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
    public function getAssignedIncidents(string $id){
        $userTeam = $this->resolveUserTeam((int) $id);
        $incidentCoords = collect();

        if ($userTeam) {
            $incidentCoords = Incident::with(['marker', 'team', 'reporter'])
                ->where('team_id', $userTeam->team_id)
                ->whereNotIn('status', [self::STATUS_DB_CONTROLLED, self::STATUS_DB_CLOSED])
                ->orderByDesc('created_at')
                ->get()
                ->map(function (Incident $incident) {
                    return [
                        ...$incident->toArray(),
                        'incident_status_id' => $this->getIncidentStatusId($incident->status),
                        'incident_status_name' => $this->getIncidentStatusLabel($incident->status),
                        'incident_type_id' => null,
                        'incident_type_name' => $this->getIncidentTypeLabel($incident),
                    ];
                })
                ->values();
        }

        return response()->json(['success'=> true, 'data'=>$incidentCoords]);
    }

    public function incidentView(){
        $currentUserId = Auth::user()->user_id;
        $registryData = $this->buildFirefighterIncidentRegistryData($currentUserId);

        return view('firefighters.incidents', [
            'stationOfficer' => $registryData['station_officer'],
            'userTeam' => $registryData['team'],
            'initialIncidentStats' => $registryData['stats'],
        ]);
    }

    public function incidentRegistryData()
    {
        $currentUserId = Auth::user()->user_id;

        return response()->json([
            'success' => true,
            'data' => $this->buildFirefighterIncidentRegistryData($currentUserId),
        ]);
    }

    public function haversineDistance($lat1, $lng1, $lat2, $lng2) {
        $R = 6371; // Earth radius in km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        $distance = $R * $c;
        
        return $distance; // in km
    }
    public function viewAll(){
        return response()->json([
            'success' => false,
            'message' => 'Legacy coordinate view is no longer supported.'
        ], 410);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
