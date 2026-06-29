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
use App\Models\EventActivity;
use App\Support\MediaPath;
use Illuminate\Support\Facades\Auth;



use App\Models\Team;


use App\Models\User;


use App\Models\Equipment;
use App\Models\EquipmentToStation;



use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\BoundaryMatcherService;
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

    protected function serializeScheduleEvent(EventActivity $event, int $currentUserId): array
    {
        $isPersonal = (int) ($event->user_id ?? 0) === $currentUserId;

        return [
            'id' => $event->event_id,
            'event_id' => $event->event_id,
            'title' => $event->event,
            'event' => $event->event,
            'type' => $event->activity_type ?: 'operations',
            'activity_type' => $event->activity_type ?: 'operations',
            'date' => optional($event->event_date)->toDateString(),
            'event_date' => optional($event->event_date)->toDateString(),
            'startTime' => $event->start_time,
            'start_time' => $event->start_time,
            'endTime' => $event->end_time,
            'end_time' => $event->end_time,
            'timeLabel' => $event->schedule,
            'schedule' => $event->schedule,
            'location' => $event->barangay,
            'barangay' => $event->barangay,
            'description' => $event->notes,
            'notes' => $event->notes,
            'status' => $event->status,
            'station_id' => $event->station_id,
            'user_id' => $event->user_id,
            'source_type' => $isPersonal ? 'personal' : 'official',
            'source_label' => $isPersonal ? 'My Personal Schedule' : 'Chief Official Schedule',
            'can_edit' => $isPersonal,
            'can_delete' => $isPersonal,
            'is_personal' => $isPersonal,
        ];
    }

    protected function validatePersonalSchedulePayload(Request $request): array
    {
        return $request->validate([
            'event' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'activity_type' => ['nullable', Rule::in(['operations', 'drill', 'community', 'inspection', 'command', 'logistics'])],
            'status' => ['nullable', Rule::in(['planned', 'confirmed', 'completed'])],
            'barangay' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function buildScheduleLabel(string $startTime, string $endTime): string
    {
        return Carbon::createFromFormat('H:i', $startTime)->format('g:i A')
            . ' - '
            . Carbon::createFromFormat('H:i', $endTime)->format('g:i A');
    }

    protected function assertPersonalScheduleWindow(array $validated): ?\Illuminate\Http\JsonResponse
    {
        $manilaNow = Carbon::now('Asia/Manila');
        $eventDate = Carbon::parse($validated['event_date'], 'Asia/Manila');

        if ($eventDate->isSameDay($manilaNow)) {
            $minimumStartHour = $manilaNow->minute > 0 ? $manilaNow->hour + 1 : $manilaNow->hour;
            $requestedStartHour = (int) Carbon::createFromFormat('H:i', $validated['start_time'], 'Asia/Manila')->format('H');

            if ($requestedStartHour < $minimumStartHour) {
                return response()->json([
                    'success' => false,
                    'message' => 'For today, only current or future Asia/Manila time slots can be scheduled.'
                ], 422);
            }
        }

        return null;
    }

    protected function hasPersonalScheduleConflict(int $userId, array $validated, ?int $ignoreEventId = null): bool
    {
        return EventActivity::query()
            ->where('user_id', $userId)
            ->whereDate('event_date', $validated['event_date'])
            ->when($ignoreEventId, function ($query) use ($ignoreEventId) {
                $query->where('event_id', '!=', $ignoreEventId);
            })
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();
    }

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

        $normalizedPath = trim($path);

        if ($normalizedPath === '') {
            return null;
        }

        return MediaPath::url($normalizedPath);
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

    public function myStation(BoundaryMatcherService $boundaryMatcherService){
        $userId = Auth::user()->user_id;
        $user = User::with(['role', 'address.barangay.city'])->where('user_id', $userId)->firstOrFail();
        $stationOfficer = StationOfficer::with([
                'user.role',
                'station.marker',
                'station.profileMedia',
                'station.galleryMedia',
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

        $spotReports = SpotInvestigationReport::with(['incident.marker', 'investigator.user', 'progressInvestigation', 'notedBy.user', 'approvedBy.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(10, ['*'], 'spot_page')
            ->withQueryString();

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'fireOrigin', 'investigator.user', 'notedBy.user', 'approvedBy.user', 'finalInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(10, ['*'], 'progress_page')
            ->withQueryString();

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.fireOrigin', 'notedBy.user', 'approvedBy.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate(10, ['*'], 'final_page')
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

        $availableSpotReports = SpotInvestigationReport::with(['incident.marker', 'incident.barangay.city'])
            ->whereDoesntHave('progressInvestigation')
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->get();

        $availableProgressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'spotInvestigation.incident.barangay.city'])
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

        return view('firefighters.schedule', compact(
            'userinfo',
            'stationOfficer',
            'stationId',
            'stationName',
            'scheduleEvents'
        ));
    }

    public function storeScheduleEvent(Request $request)
    {
        $userId = Auth::user()->user_id;
        $stationOfficer = $this->getStationOfficerForUserId($userId);
        $stationId = $stationOfficer?->station_id;

        if (! $stationId) {
            return response()->json([
                'success' => false,
                'message' => 'No station is linked to your firefighter account.'
            ], 422);
        }

        $validated = $this->validatePersonalSchedulePayload($request);
        $windowError = $this->assertPersonalScheduleWindow($validated);

        if ($windowError) {
            return $windowError;
        }

        if ($this->hasPersonalScheduleConflict($userId, $validated)) {
            return response()->json([
                'success' => false,
                'message' => 'This time overlaps one of your personal schedules.'
            ], 422);
        }

        $event = EventActivity::create([
            'event' => $validated['event'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'schedule' => $this->buildScheduleLabel($validated['start_time'], $validated['end_time']),
            'activity_type' => $validated['activity_type'] ?? 'operations',
            'status' => $validated['status'] ?? 'planned',
            'barangay' => $validated['barangay'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'station_id' => $stationId,
            'user_id' => $userId,
        ]);

        UserActivityLogger::log('created personal schedule event', $userId, $request);

        return response()->json([
            'success' => true,
            'message' => 'Personal schedule saved successfully.',
            'data' => $this->serializeScheduleEvent($event, $userId),
        ]);
    }

    public function updateScheduleEvent(Request $request, EventActivity $eventActivity)
    {
        $userId = Auth::user()->user_id;

        if ((int) ($eventActivity->user_id ?? 0) !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own personal schedule.'
            ], 403);
        }

        $validated = $this->validatePersonalSchedulePayload($request);
        $windowError = $this->assertPersonalScheduleWindow($validated);

        if ($windowError) {
            return $windowError;
        }

        if ($this->hasPersonalScheduleConflict($userId, $validated, (int) $eventActivity->event_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This time overlaps one of your personal schedules.'
            ], 422);
        }

        $eventActivity->update([
            'event' => $validated['event'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'schedule' => $this->buildScheduleLabel($validated['start_time'], $validated['end_time']),
            'activity_type' => $validated['activity_type'] ?? 'operations',
            'status' => $validated['status'] ?? 'planned',
            'barangay' => $validated['barangay'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        UserActivityLogger::log('updated personal schedule event', $userId, $request);

        return response()->json([
            'success' => true,
            'message' => 'Personal schedule updated successfully.',
            'data' => $this->serializeScheduleEvent($eventActivity->fresh(), $userId),
        ]);
    }

    public function destroyScheduleEvent(Request $request, EventActivity $eventActivity)
    {
        $userId = Auth::user()->user_id;

        if ((int) ($eventActivity->user_id ?? 0) !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own personal schedule.'
            ], 403);
        }

        $deletedEventId = $eventActivity->event_id;
        $eventActivity->delete();
        UserActivityLogger::log('deleted personal schedule event', $userId, $request);

        return response()->json([
            'success' => true,
            'message' => 'Personal schedule deleted successfully.',
            'data' => [
                'event_id' => $deletedEventId,
            ],
        ]);
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
