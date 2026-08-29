<?php

namespace App\Http\Controllers\staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
// use App\Models\Coordinate;
use App\Models\Post;
use App\Models\Station;
use App\Models\Incident;
use App\Models\VoiceCall;
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
use App\Models\DutyException;
use App\Models\DutyShift;
use App\Services\DutyRosterRecurrenceService;
use App\Services\IncidentResourceDispatchService;
use App\Services\IncidentResponderSnapshotService;
use App\Support\MediaPath;
use App\Services\ProfileImageService;
use App\Support\MarkerFeedBuilder;
use Illuminate\Support\Facades\Auth;



use App\Models\Team;


use App\Models\User;


use App\Models\Equipment;
use App\Models\EquipmentToStation;
use App\Models\Apparatus;



use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\BoundaryMatcherService;
use App\Support\UserActivityLogger;

class FirefighterController extends Controller
{
    private const REGISTRY_CACHE_SECONDS = 15;
    private const REGISTRY_DETAIL_CACHE_SECONDS = 60;
    private const ASSIGNED_INCIDENTS_CACHE_SECONDS = 10;
    private const DASHBOARD_CACHE_SECONDS = 20;
    private const ACTIVE_FIREFIGHTER_LOCATIONS_CACHE_SECONDS = 12;
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

    protected function serializeDutyShift(DutyShift $shift, int $stationOfficerId, ?DutyException $exception = null): array
    {
        $assignment = $shift->assignments->firstWhere('station_officer_id', $stationOfficerId);
        $roleLabel = match ($assignment?->team_role) {
            'team_leader' => 'Team Leader',
            'commander_driver' => 'Driver',
            'pumper' => 'Pumper',
            'firefighter_officer_1' => 'Firefighter Officer 1',
            'firefighter_officer_2' => 'Firefighter Officer 2',
            'firefighter_officer_3' => 'Firefighter Officer 3',
            'rescue_radio' => 'Rescue / Radio',
            default => 'Firefighter',
        };
        $isOvernight = $shift->starts_at?->toDateString() !== $shift->ends_at?->toDateString();
        $availability = $exception?->type === 'leave' ? 'On Leave' : ($exception?->type === 'rest_day' ? 'Rest Day' : null);
        $now = now();
        $dutyStatus = $availability ?: ($shift->starts_at?->lte($now) && $shift->ends_at?->gt($now) ? 'On Duty' : ($shift->ends_at?->lte($now) ? 'Completed' : 'Scheduled'));
        $recurrenceLabel = match ($shift->template?->recurrence_type) {
            'daily' => 'Daily',
            'weekdays' => 'Selected weekdays',
            default => 'Weekly',
        };
        $details = ["Assigned as {$roleLabel}", $shift->duty_roster_template_id ? "{$recurrenceLabel} recurring shift" : 'One-time duty shift'];
        if ($exception?->reason) $details[] = $exception->reason;
        if ($shift->notes) $details[] = $shift->notes;

        return [
            'id' => 'duty-'.$shift->duty_shift_id,
            'event_id' => 'duty-'.$shift->duty_shift_id,
            'title' => "Team {$shift->team_id} Duty Shift",
            'event' => "Team {$shift->team_id} Duty Shift",
            'type' => 'duty',
            'activity_type' => 'duty',
            'date' => $shift->starts_at?->toDateString(),
            'event_date' => $shift->starts_at?->toDateString(),
            'startTime' => $shift->starts_at?->format('H:i'),
            'start_time' => $shift->starts_at?->format('H:i'),
            'endTime' => $shift->ends_at?->format('H:i'),
            'end_time' => $shift->ends_at?->format('H:i'),
            'timeLabel' => $shift->starts_at?->format('g:i A').' - '.$shift->ends_at?->format('g:i A').($isOvernight ? ' (next day)' : ''),
            'schedule' => $shift->starts_at?->format('g:i A').' - '.$shift->ends_at?->format('g:i A').($isOvernight ? ' (next day)' : ''),
            'location' => $shift->team?->station?->station_name,
            'barangay' => $shift->team?->station?->station_name,
            'description' => implode(' · ', $details),
            'notes' => implode(' · ', $details),
            'status' => strtolower(str_replace(' ', '_', $dutyStatus)),
            'status_label' => $dutyStatus,
            'station_id' => $shift->station_id,
            'team_id' => $shift->team_id,
            'duty_role' => $assignment?->team_role,
            'duty_role_label' => $roleLabel,
            'is_recurring' => (bool) $shift->duty_roster_template_id,
            'recurrence_type' => $shift->template?->recurrence_type ?: ($shift->duty_roster_template_id ? 'weekly' : 'none'),
            'recurrence_label' => $shift->duty_roster_template_id ? $recurrenceLabel : null,
            'is_override' => (bool) $shift->is_override,
            'availability_type' => $exception?->type,
            'source_type' => 'duty',
            'source_label' => 'Official Duty Shift',
            'can_edit' => false,
            'can_delete' => false,
            'is_personal' => false,
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
        return Cache::rememberForever('schema:user-location-table', function () {
            if (\Illuminate\Support\Facades\Schema::hasTable('user_locations')) {
                return 'user_locations';
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('user_location')) {
                return 'user_location';
            }

            return null;
        });
    }

    protected function getStationOfficerForUserId(?int $userId, bool $withStation = false): ?StationOfficer
    {
        if (!$userId) {
            return null;
        }

        $cacheKey = $withStation
            ? "firefighter:station-officer:{$userId}:with-station"
            : "firefighter:station-officer:{$userId}";

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(60),
            function () use ($userId, $withStation) {
                $query = StationOfficer::query()
                    ->select(['station_officer_id', 'station_id', 'user_id', 'rank'])
                    ->where('user_id', $userId);

                if ($withStation) {
                    $query->with('station');
                }

                return $query->first();
            }
        );
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

    protected function resolveUserTeam(?int $userId, ?StationOfficer $stationOfficer = null): ?Team
    {
        if (!$userId) {
            return null;
        }

        return Cache::remember(
            "firefighter:user-team:{$userId}",
            now()->addSeconds(60),
            function () use ($userId, $stationOfficer) {
                $resolvedStationOfficer = $stationOfficer ?: $this->getStationOfficerForUserId($userId);
                $stationOfficerId = $resolvedStationOfficer?->station_officer_id;

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
        );
    }

    protected function getLatestRouteTimestampsForIncidentIds(array $incidentIds): array
    {
        if (empty($incidentIds)) {
            return [];
        }

        return DB::table('routes')
            ->selectRaw('incident_id, MAX(COALESCE(updated_at, last_updated_at, started_at, created_at)) as latest_route_timestamp')
            ->whereIn('incident_id', $incidentIds)
            ->groupBy('incident_id')
            ->pluck('latest_route_timestamp', 'incident_id')
            ->toArray();
    }

    protected function loadTeamIncidentsForRegistry(int $teamId, bool $activeOnly = false)
    {
        return Incident::query()
            ->select([
                'incident_id',
                'marker_id',
                'status',
                'team_id',
                'reported_by',
                'station_id',
                'barangay_id',
                'dispatch_assigned_at',
                'incident_completed_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'marker:marker_id,title,description,lat,lng,status,category,image',
                'reporter:user_id,first_name,last_name',
                'team:team_id',
                'barangay:barangay_id,barangay_name,city_id',
                'barangay.city:city_id,city_name',
                'spotInvestigationReport:spot_investigation_report_id,incident_id,place_of_occurrence',
                'spotInvestigationReport.progressInvestigation:progress_investigation_report_id,spot_investigation_id',
                'spotInvestigationReport.progressInvestigation.finalInvestigation:final_investigation_report_id,progress_investigation_id,exact_address',
            ])
            ->where(function ($query) use ($teamId) {
                $query->where('team_id', $teamId)
                    ->orWhereHas('responseUnits', fn ($unitQuery) => $unitQuery->where('team_id', $teamId));
            })
            ->when($activeOnly, function ($query) {
                $query->whereNotIn('status', [self::STATUS_DB_CONTROLLED, self::STATUS_DB_CLOSED]);
            })
            ->orderByDesc('created_at')
            ->get();
    }

    protected function transformAssignedIncident(Incident $incident): array
    {
        $marker = $incident->marker;
        $reporter = $incident->reporter;
        $team = $incident->team;
        $dispatch = app(\App\Services\IncidentDispatchStateService::class)->describe($incident);

        return [
            ...$dispatch,
            'incident_id' => $incident->incident_id,
            'status' => $incident->status,
            'incident_status_id' => $this->getIncidentStatusId($incident->status),
            'incident_status_name' => $this->getIncidentStatusLabel($incident->status),
            'incident_type_id' => null,
            'incident_type_name' => $this->getIncidentTypeLabel($incident),
            'team_id' => $incident->team_id,
            'reported_by' => $incident->reported_by,
            'created_at' => optional($incident->created_at)->toIso8601String(),
            'updated_at' => optional($incident->updated_at)->toIso8601String(),
            'team' => $team ? [
                'team_id' => $team->team_id,
                'team_name' => $team->team_name ?? ('Team #' . $team->team_id),
            ] : null,
            'reporter' => $reporter ? [
                'user_id' => $reporter->user_id,
                'first_name' => $reporter->first_name,
                'last_name' => $reporter->last_name,
            ] : null,
            'marker' => $marker ? [
                'marker_id' => $marker->marker_id,
                'title' => $marker->title,
                'description' => $marker->description,
                'lat' => $marker->lat,
                'lng' => $marker->lng,
                'category' => $marker->category,
                'image' => $this->normalizeStoredMediaPath($marker->image),
            ] : null,
        ];
    }

    protected function resolveIncidentPrimaryLocation(?string $markerTitle, ?string $markerDescription): ?string
    {
        $markerTitle = trim((string) $markerTitle);
        $markerDescription = trim((string) $markerDescription);

        $genericIncidentMarkerTitles = ['help emergency', 'fire emergency', 'fire emergency voice call', 'fire emergency from voice call', 'emergency', 'incident'];
        $genericIncidentMarkerDescriptions = [
            'fire emergency reported by citizen.',
            'fire emergency reported by citizen',
            'reported by citizen',
            'emergency reported by citizen',
        ];

        $normalizedTitle = strtolower($markerTitle);
        $normalizedDescription = strtolower($markerDescription);
        $hasGenericTitle = in_array($normalizedTitle, $genericIncidentMarkerTitles, true)
            || str_contains($normalizedTitle, 'emergency voice call')
            || str_contains($normalizedTitle, 'emergency from voice call');
        $hasGenericDescription = in_array($normalizedDescription, $genericIncidentMarkerDescriptions, true);

        if ($hasGenericTitle && $hasGenericDescription) {
            return null;
        }

        if ($hasGenericTitle) {
            return $markerDescription ?: null;
        }

        return $markerTitle ?: ($markerDescription ?: null);
    }

    protected function buildIncidentReferenceLabel(?string $primaryLocation, ?string $barangayName, ?string $cityName, $timestamp, string $fallbackLabel = 'Reported incident'): string
    {
        $locationLabel = trim((string) $primaryLocation) ?: $fallbackLabel;
        $localityLabel = collect([$barangayName, $cityName])->filter()->join(', ') ?: 'Barangay not assigned';

        $dateLabel = 'Date unavailable';
        if ($timestamp instanceof Carbon) {
            $dateLabel = $timestamp->format('M d, Y h:i A');
        } elseif ($timestamp) {
            try {
                $dateLabel = Carbon::parse($timestamp)->format('M d, Y h:i A');
            } catch (\Throwable $exception) {
                $dateLabel = 'Date unavailable';
            }
        }

        return collect([$locationLabel, $localityLabel, $dateLabel])->join(' | ');
    }

    protected function getCurrentOfficerName(?StationOfficer $stationOfficer): string
    {
        return trim((string) (($stationOfficer?->user?->first_name ?? '') . ' ' . ($stationOfficer?->user?->last_name ?? '')))
            ?: 'Current Station Officer';
    }

    protected function buildStationAddress(?Station $station): string
    {
        return collect([
            $station?->address?->street,
            $station?->address?->barangay?->barangay_name,
            $station?->address?->barangay?->city?->city_name,
        ])->filter()->join(', ');
    }

    protected function getStationShellContext(int $userId): array
    {
        $cacheKey = "firefighter:station-shell:{$userId}";

        return Cache::remember($cacheKey, now()->addSeconds(45), function () use ($userId) {
            $user = User::query()
                ->select(['user_id', 'first_name', 'last_name', 'email', 'contact_number', 'image', 'profile_thumbnail', 'role_id', 'address_id'])
                ->with([
                    'role:role_id,role',
                    'address:address_id,street,barangay_id',
                    'address.barangay:barangay_id,barangay_name,city_id',
                    'address.barangay.city:city_id,city_name',
                ])
                ->where('user_id', $userId)
                ->firstOrFail();

            $stationOfficer = StationOfficer::query()
                ->select(['station_officer_id', 'station_id', 'user_id', 'rank', 'status'])
                ->with([
                    'user:user_id,first_name,last_name,email,contact_number,role_id',
                    'user.role:role_id,role',
                    'station:station_id,station_name,station_code,contact_number,email,address_id,marker_id',
                    'station.marker:marker_id,title,image',
                    'station.profileMedia:station_media_id,station_id,path,caption',
                    'station.address:address_id,street,barangay_id',
                    'station.address.barangay:barangay_id,barangay_name,city_id',
                    'station.address.barangay.city:city_id,city_name',
                ])
                ->where('user_id', $userId)
                ->firstOrFail();

            $station = $stationOfficer->station;
            $stationImagePath = $station?->profileMedia?->path
                ?? $station?->marker?->image;

            return [
                'user' => $user,
                'stationOfficer' => $stationOfficer,
                'station' => $station,
                'stationId' => $stationOfficer->station_id,
                'currentOfficerName' => $this->getCurrentOfficerName($stationOfficer),
                'stationAddress' => $this->buildStationAddress($station),
                'stationImageUrl' => $stationImagePath ? MediaPath::url($stationImagePath) : null,
            ];
        });
    }

    protected function getStationOfficerIdsForStation(int $stationId)
    {
        return Cache::remember("firefighter:station-officer-ids:{$stationId}", now()->addSeconds(45), function () use ($stationId) {
            return StationOfficer::query()
                ->where('station_id', $stationId)
                ->pluck('station_officer_id');
        });
    }

    protected function getStationTeamIdsForOfficerIds($stationOfficerIds)
    {
        $officerIds = collect($stationOfficerIds)->filter()->values()->all();

        if (empty($officerIds)) {
            return collect();
        }

        $cacheKey = 'firefighter:station-team-ids:' . md5(json_encode($officerIds));

        return Cache::remember($cacheKey, now()->addSeconds(45), function () use ($officerIds) {
            return Team::query()
                ->where(function ($query) use ($officerIds) {
                    foreach ([
                        'commander_driver',
                        'pumper',
                        'team_leader',
                        'firefighter_officer_1',
                        'firefighter_officer_2',
                        'firefighter_officer_3',
                        'rescue_radio',
                    ] as $column) {
                        $query->orWhereIn($column, $officerIds);
                    }
                })
                ->pluck('team_id');
        });
    }

    protected function loadStationOfficerRoster(int $stationId)
    {
        return StationOfficer::query()
            ->select(['station_officer_id', 'station_id', 'user_id', 'rank', 'status'])
            ->with([
                'user:user_id,first_name,last_name,email,contact_number,image,role_id,profile_thumbnail',
                'user.role:role_id,role',
            ])
            ->where('station_id', $stationId)
            ->orderBy('rank')
            ->orderBy('station_officer_id')
            ->get();
    }

    protected function loadCrewMembersForTeam(?Team $userTeam)
    {
        if (! $userTeam) {
            return collect();
        }

        $teamMemberColumns = [
            'commander_driver' => 'Commander / Driver',
            'pumper' => 'Pumper',
            'team_leader' => 'Team Leader',
            'firefighter_officer_1' => 'Firefighter Officer 1',
            'firefighter_officer_2' => 'Firefighter Officer 2',
            'firefighter_officer_3' => 'Firefighter Officer 3',
            'rescue_radio' => 'Rescue Radio',
        ];

        $teamOfficerIds = collect(array_keys($teamMemberColumns))
            ->map(fn ($column) => $userTeam->{$column})
            ->filter()
            ->values();

        if ($teamOfficerIds->isEmpty()) {
            return collect();
        }

        $membersById = StationOfficer::query()
            ->select(['station_officer_id', 'station_id', 'user_id', 'rank', 'status'])
            ->with([
                'user:user_id,first_name,last_name,email,contact_number,image,role_id,profile_thumbnail',
                'user.role:role_id,role',
            ])
            ->whereIn('station_officer_id', $teamOfficerIds)
            ->get()
            ->keyBy('station_officer_id');

        return collect($teamMemberColumns)
            ->map(function ($label, $column) use ($userTeam, $membersById) {
                $memberOfficerId = $userTeam->{$column};
                if (! $memberOfficerId) {
                    return null;
                }

                $member = $membersById->get($memberOfficerId);
                if (! $member?->user) {
                    return null;
                }

                return (object) [
                    'user_id' => $member->user->user_id,
                    'station_officer_id' => $member->station_officer_id,
                    'name' => trim(($member->user->first_name ?? '') . ' ' . ($member->user->last_name ?? '')) ?: ('Officer #' . $memberOfficerId),
                    'role_label' => $label,
                    'role_name' => $member->user->role?->role,
                    'rank' => $member->rank ?: 'Rank not set',
                    'status' => $member->status ?: 'available',
                    'email' => $member->user->email,
                    'contact_number' => $member->user->contact_number,
                    'profile_image_url' => $member->user->profile_image_url,
                ];
            })
            ->filter()
            ->values();
    }

    protected function loadStationEquipmentAssignments(int $stationId)
    {
        return EquipmentToStation::query()
            ->select([
                'equipment_station_id',
                'station_id',
                'equipment_id',
                'available_quantity',
                'maintenance_quantity',
                'damaged_quantity',
            ])
            ->with([
                'equipment:equipment_id,equipment_name,category,type',
            ])
            ->where('station_id', $stationId)
            ->orderBy('equipment_station_id')
            ->get();
    }

    protected function loadStationApparatus(int $stationId)
    {
        return Apparatus::query()
            ->select([
                'apparatus_id',
                'station_id',
                'equipment_id',
                'unit_number',
                'plate_number',
                'apparatus_type',
                'status',
                'maintenance_status',
                'last_maintenance_at',
                'next_maintenance_due_at',
                'notes',
            ])
            ->with('equipment:equipment_id,equipment_name,category,type')
            ->where('station_id', $stationId)
            ->orderBy('unit_number')
            ->get();
    }

    protected function loadSpotReportsPaginator($stationOfficerIds, int $perPage = 10)
    {
        return SpotInvestigationReport::query()
            ->with(['incident.marker', 'investigator.user.role', 'progressInvestigation', 'notedBy.user', 'approvedBy.user', 'proofImages'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    protected function loadProgressReportsPaginator($stationOfficerIds, int $perPage = 10)
    {
        return ProgressInvestigationReport::query()
            ->with(['spotInvestigation.incident.marker', 'fireOrigin', 'investigator.user.role', 'notedBy.user', 'approvedBy.user', 'finalInvestigation', 'proofImages'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    protected function loadFinalReportsPaginator($stationOfficerIds, int $perPage = 10)
    {
        return FinalInvestigationReport::query()
            ->with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.fireOrigin', 'investigator.user.role', 'notedBy.user', 'approvedBy.user', 'proofImages'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    protected function mapSpotReportStore($reports, string $currentOfficerName): array
    {
        return collect($reports)->mapWithKeys(function ($report) use ($currentOfficerName) {
            $incidentReferenceLabel = $this->buildIncidentReferenceLabel(
                $this->resolveIncidentPrimaryLocation(
                    $report->incident?->marker?->title,
                    $report->incident?->marker?->description
                ),
                $report->incident?->resolved_dropdown_barangay_name ?? $report->incident?->barangay?->barangay_name,
                $report->incident?->resolved_dropdown_city_name ?? $report->incident?->barangay?->city?->city_name,
                $report->fire_started ?? $report->incident?->created_at ?? $report->created_at,
                'Reported incident'
            );

            return [
                $report->spot_investigation_report_id => [
                    'id' => $report->spot_investigation_report_id,
                    'type' => 'spot',
                    'incident_id' => $report->incident_id,
                    'incident_location' => $incidentReferenceLabel,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: $currentOfficerName,
                    'fire_started' => optional($report->fire_started)->toIso8601String(),
                    'fire_out' => optional($report->fire_out)->toIso8601String(),
                    'estimated_damage' => $report->estimated_damage,
                    'occupancy' => $report->occupancy,
                    'estimated_area' => $report->estimated_area,
                    'dead' => $report->dead,
                    'injured' => $report->injured,
                    'owner' => $report->owner,
                    'report_completed_at' => optional($report->report_completed_at)->toIso8601String(),
                    'memorandum_for' => $report->memorandum_for,
                    'place_of_occurrence' => $report->place_of_occurrence,
                    'involved_structure' => $report->involved_structure,
                    'establishment_name' => $report->establishment_name,
                    'occupant' => $report->occupant,
                    'highest_alarm_level' => $report->highest_alarm_level,
                    'affected_establishments_count' => $report->affected_establishments_count,
                    'description' => $report->description,
                    'fatality_details' => $report->fatality_details,
                    'details_of_investigation' => $report->details_of_investigation,
                    'disposition' => $report->disposition,
                    'noted_by_name' => trim(($report->notedBy?->user?->first_name ?? '') . ' ' . ($report->notedBy?->user?->last_name ?? '')),
                    'approved_by_name' => trim(($report->approvedBy?->user?->first_name ?? '') . ' ' . ($report->approvedBy?->user?->last_name ?? '')),
                    'noted_by_station_officer_id' => $report->noted_by_station_officer_id,
                    'approved_by_station_officer_id' => $report->approved_by_station_officer_id,
                    'proof_images' => $this->mapInvestigationProofImages($report->proofImages),
                    'has_progress' => (bool) $report->progressInvestigation,
                ],
            ];
        })->all();
    }

    protected function mapProgressReportStore($reports, string $currentOfficerName): array
    {
        return collect($reports)->mapWithKeys(function ($report) use ($currentOfficerName) {
            $incidentReferenceLabel = $this->buildIncidentReferenceLabel(
                $this->resolveIncidentPrimaryLocation(
                    $report->spotInvestigation?->incident?->marker?->title,
                    $report->spotInvestigation?->incident?->marker?->description
                ),
                $report->spotInvestigation?->incident?->resolved_dropdown_barangay_name ?? $report->spotInvestigation?->incident?->barangay?->barangay_name,
                $report->spotInvestigation?->incident?->resolved_dropdown_city_name ?? $report->spotInvestigation?->incident?->barangay?->city?->city_name,
                $report->report_completed_at ?? $report->created_at ?? $report->spotInvestigation?->fire_started,
                'Spot investigation'
            );

            return [
                $report->progress_investigation_report_id => [
                    'id' => $report->progress_investigation_report_id,
                    'type' => 'progress',
                    'spot_investigation_id' => $report->spot_investigation_id,
                    'incident_location' => $incidentReferenceLabel,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: $currentOfficerName,
                    'fire_origin_id' => $report->fire_origin_id,
                    'fire_origin_name' => $report->fireOrigin?->origin,
                    'fire_cause' => $report->fire_cause,
                    'responsible_arson' => $report->responsible_arson,
                    'report_completed_at' => optional($report->report_completed_at)->toIso8601String(),
                    'memorandum_for' => $report->memorandum_for,
                    'mission_order_number' => $report->mission_order_number,
                    'mission_order_date' => optional($report->mission_order_date)->format('Y-m-d'),
                    'facts_of_case' => $report->facts_of_case,
                    'disposition' => $report->disposition,
                    'noted_by_name' => trim(($report->notedBy?->user?->first_name ?? '') . ' ' . ($report->notedBy?->user?->last_name ?? '')),
                    'approved_by_name' => trim(($report->approvedBy?->user?->first_name ?? '') . ' ' . ($report->approvedBy?->user?->last_name ?? '')),
                    'noted_by_station_officer_id' => $report->noted_by_station_officer_id,
                    'approved_by_station_officer_id' => $report->approved_by_station_officer_id,
                    'proof_images' => $this->mapInvestigationProofImages($report->proofImages),
                    'has_final' => (bool) $report->finalInvestigation,
                ],
            ];
        })->all();
    }

    protected function mapFinalReportStore($reports, string $currentOfficerName): array
    {
        return collect($reports)->mapWithKeys(function ($report) use ($currentOfficerName) {
            $incidentReferenceLabel = $this->buildIncidentReferenceLabel(
                $this->resolveIncidentPrimaryLocation(
                    $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title,
                    $report->progressInvestigation?->spotInvestigation?->incident?->marker?->description
                ),
                $report->progressInvestigation?->spotInvestigation?->incident?->resolved_dropdown_barangay_name ?? $report->progressInvestigation?->spotInvestigation?->incident?->barangay?->barangay_name,
                $report->progressInvestigation?->spotInvestigation?->incident?->resolved_dropdown_city_name ?? $report->progressInvestigation?->spotInvestigation?->incident?->barangay?->city?->city_name,
                $report->created_at ?? $report->progressInvestigation?->report_completed_at,
                'Progress investigation'
            );

            return [
                $report->final_investigation_report_id => [
                    'id' => $report->final_investigation_report_id,
                    'type' => 'final',
                    'progress_investigation_id' => $report->progress_investigation_id,
                    'incident_location' => $incidentReferenceLabel,
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')) ?: $currentOfficerName,
                    'report_completed_at' => optional($report->report_completed_at)->toIso8601String(),
                    'memorandum_for' => $report->memorandum_for,
                    'exact_address' => $report->exact_address,
                    'office_address' => $report->office_address,
                    'alarm_at' => optional($report->alarm_at)->toIso8601String(),
                    'establishment_burned' => $report->establishment_burned,
                    'fire_victims' => $report->fire_victims,
                    'origin_of_fire' => $report->origin_of_fire,
                    'cause_of_fire' => $report->cause_of_fire,
                    'total_damage' => $report->total_damage,
                    'created_at' => optional($report->created_at)->toIso8601String(),
                    'fire_origin_name' => $report->progressInvestigation?->fireOrigin?->origin,
                    'fire_cause' => $report->progressInvestigation?->fire_cause,
                    'substantiating_documents' => $report->substantiating_documents ?? [],
                    'facts_of_case' => $report->facts_of_case,
                    'discussion' => $report->discussion,
                    'findings' => $report->findings,
                    'recommendation' => $report->recommendation,
                    'noted_by_name' => trim(($report->notedBy?->user?->first_name ?? '') . ' ' . ($report->notedBy?->user?->last_name ?? '')),
                    'approved_by_name' => trim(($report->approvedBy?->user?->first_name ?? '') . ' ' . ($report->approvedBy?->user?->last_name ?? '')),
                    'noted_by_station_officer_id' => $report->noted_by_station_officer_id,
                    'approved_by_station_officer_id' => $report->approved_by_station_officer_id,
                    'proof_images' => $this->mapInvestigationProofImages($report->proofImages),
                ],
            ];
        })->all();
    }

    protected function mapInvestigationProofImages($images): array
    {
        return collect($images)->map(fn ($image) => [
            'id' => $image->investigation_report_image_id,
            'name' => $image->file_name,
            'url' => MediaPath::url($image->file_path),
            'size' => (int) $image->file_size,
        ])->values()->all();
    }

    protected function loadAvailableSpotIncidents($stationTeamIds, BoundaryMatcherService $boundaryMatcherService)
    {
        return Incident::with(['marker', 'barangay.city'])
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
    }

    protected function buildOfficerOptions($stationOfficers): array
    {
        return collect($stationOfficers)->map(function ($officer) {
            return [
                'id' => $officer->station_officer_id,
                'label' => trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? ''))
                    ?: ('Officer #' . $officer->station_officer_id),
            ];
        })->values()->all();
    }

    protected function buildSpotIncidentOptions($incidents): array
    {
        return collect($incidents)->map(function ($incident) {
            $label = $this->buildIncidentReferenceLabel(
                $this->resolveIncidentPrimaryLocation(
                    $incident->marker?->title,
                    $incident->marker?->description
                ),
                $incident->resolved_dropdown_barangay_name ?? $incident->barangay?->barangay_name,
                $incident->resolved_dropdown_city_name ?? $incident->barangay?->city?->city_name,
                $incident->created_at ?? $incident->updated_at,
                'Reported incident'
            );

            return [
                'id' => $incident->incident_id,
                'label' => $label,
                'updated_at' => optional($incident->updated_at)->format('Y-m-d\TH:i'),
                'occurred_at' => optional($incident->occurred_at)->format('Y-m-d\TH:i'),
                'occurred_at_verified' => (bool) $incident->occurred_at_verified_at,
            ];
        })->values()->all();
    }

    protected function buildSpotReportOptions($reports): array
    {
        return collect($reports)->map(function ($report) {
            return [
                'id' => $report->spot_investigation_report_id,
                'label' => $this->buildIncidentReferenceLabel(
                    $this->resolveIncidentPrimaryLocation(
                        $report->incident?->marker?->title,
                        $report->incident?->marker?->description
                    ),
                    $report->incident?->resolved_dropdown_barangay_name ?? $report->incident?->barangay?->barangay_name,
                    $report->incident?->resolved_dropdown_city_name ?? $report->incident?->barangay?->city?->city_name,
                    $report->incident?->created_at ?? $report->created_at,
                    'Reported incident'
                ),
            ];
        })->values()->all();
    }

    protected function buildProgressReportOptions($reports): array
    {
        return collect($reports)->map(function ($report) {
            return [
                'id' => $report->progress_investigation_report_id,
                'label' => $this->buildIncidentReferenceLabel(
                    $this->resolveIncidentPrimaryLocation(
                        $report->spotInvestigation?->incident?->marker?->title,
                        $report->spotInvestigation?->incident?->marker?->description
                    ),
                    $report->spotInvestigation?->incident?->resolved_dropdown_barangay_name ?? $report->spotInvestigation?->incident?->barangay?->barangay_name,
                    $report->spotInvestigation?->incident?->resolved_dropdown_city_name ?? $report->spotInvestigation?->incident?->barangay?->city?->city_name,
                    $report->spotInvestigation?->incident?->created_at ?? $report->created_at,
                    'Spot investigation'
                ),
            ];
        })->values()->all();
    }

    protected function buildFireOriginOptions(): array
    {
        return Cache::remember('firefighter:fire-origin-options', now()->addMinutes(5), function () {
            return FireOrigin::query()
                ->orderBy('fire_origin_id')
                ->get()
                ->map(fn ($origin) => [
                    'id' => $origin->fire_origin_id,
                    'label' => $origin->origin ?? ('Origin #' . $origin->fire_origin_id),
                ])
                ->values()
                ->all();
        });
    }

    protected function getStationOverviewCounts(int $stationId, ?Team $userTeam, $stationOfficerIds): array
    {
        $crewCount = collect([
            $userTeam?->commander_driver,
            $userTeam?->pumper,
            $userTeam?->team_leader,
            $userTeam?->firefighter_officer_1,
            $userTeam?->firefighter_officer_2,
            $userTeam?->firefighter_officer_3,
            $userTeam?->rescue_radio,
        ])->filter()->unique()->count();

        return [
            'station_officers' => StationOfficer::query()->where('station_id', $stationId)->count(),
            'crew_members' => $crewCount,
            'equipment_lines' => EquipmentToStation::query()->where('station_id', $stationId)->count(),
            'spot_reports' => SpotInvestigationReport::query()->whereIn('station_officer_id', $stationOfficerIds)->count(),
            'progress_reports' => ProgressInvestigationReport::query()->whereIn('station_officer_id', $stationOfficerIds)->count(),
            'final_reports' => FinalInvestigationReport::query()->whereIn('station_officer_id', $stationOfficerIds)->count(),
        ];
    }

    protected function buildFirefighterIncidentRegistryData(int $userId): array
    {
        return Cache::remember(
            "firefighter:registry:{$userId}",
            now()->addSeconds(self::REGISTRY_CACHE_SECONDS),
            function () use ($userId) {
                $stationOfficer = $this->getStationOfficerForUserId($userId, true);
                $userTeam = $this->resolveUserTeam($userId);
                $incidents = collect();

                if ($userTeam) {
                    $incidents = $this->loadTeamIncidentsForRegistry((int) $userTeam->team_id);
                }

                $routeArrivalMap = $this->getLatestRouteTimestampsForIncidentIds($incidents->pluck('incident_id')->all());
                $spotReportMap = SpotInvestigationReport::query()
                    ->whereIn('incident_id', $incidents->pluck('incident_id')->filter()->all())
                    ->pluck('spot_investigation_report_id', 'incident_id')
                    ->mapWithKeys(fn ($reportId, $incidentId) => [(int) $incidentId => (int) $reportId])
                    ->all();
                $voiceLocationMap = collect();
                if (\Illuminate\Support\Facades\Schema::hasTable('voice_calls')
                    && \Illuminate\Support\Facades\Schema::hasColumn('voice_calls', 'caller_location_label')) {
                    $voiceLocationMap = VoiceCall::query()
                        ->where(function ($query) use ($incidents) {
                            $ids = $incidents->pluck('incident_id')->filter()->all();
                            $query->whereIn('created_incident_id', $ids)->orWhereIn('incident_id', $ids);
                        })
                        ->whereNotNull('caller_location_label')
                        ->latest('created_at')
                        ->get(['incident_id', 'created_incident_id', 'caller_location_label'])
                        ->mapWithKeys(function (VoiceCall $call) {
                            $incidentId = (int) ($call->created_incident_id ?: $call->incident_id);
                            return $incidentId > 0 ? [$incidentId => trim((string) $call->caller_location_label)] : [];
                        });
                }

                $responseDurations = [];
                $stats = [
                    'total' => $incidents->count(),
                    'active' => 0,
                    'controlled' => 0,
                    'closed' => 0,
                    'avg_response_minutes' => 0,
                ];

                $mappedIncidents = $incidents->map(function ($incident) use (&$stats, &$responseDurations, $routeArrivalMap, $spotReportMap, $voiceLocationMap) {
                    $marker = $incident->marker;
                    $arrivalTimestamp = $routeArrivalMap[$incident->incident_id] ?? null;
                    $arrivalAt = $arrivalTimestamp ? Carbon::parse($arrivalTimestamp) : null;
                    $reporterName = trim(($incident->reporter?->first_name ?? '') . ' ' . ($incident->reporter?->last_name ?? ''));
                    $incidentStatusId = $this->getIncidentStatusId($incident->status);
                    $spotReportId = $spotReportMap[(int) $incident->incident_id] ?? null;
                    $investigationReady = $incidentStatusId === 4 && $spotReportId === null;
                    $finalAddress = trim((string) ($incident->spotInvestigationReport?->progressInvestigation?->finalInvestigation?->exact_address ?? ''));
                    $placeOfOccurrence = trim((string) ($incident->spotInvestigationReport?->place_of_occurrence ?? ''));
                    $barangayLocation = collect([
                        $incident->barangay?->barangay_name,
                        $incident->barangay?->city?->city_name,
                    ])->filter()->join(', ');
                    $markerLocation = $this->resolveIncidentPrimaryLocation($marker?->title, $marker?->description);
                    $coordinateLocation = $marker?->lat !== null && $marker?->lng !== null
                        ? number_format((float) $marker->lat, 5).', '.number_format((float) $marker->lng, 5)
                        : null;
                    $location = collect([
                        $voiceLocationMap->get((int) $incident->incident_id),
                        $finalAddress,
                        $placeOfOccurrence,
                        $barangayLocation,
                        $markerLocation,
                        $coordinateLocation,
                    ])->map(fn ($value) => trim((string) $value))->first(fn ($value) => $value !== '') ?: 'Location unavailable';

                    if (in_array($incidentStatusId, [2, 3], true)) {
                        $stats['active']++;
                    } elseif ($incidentStatusId === 4) {
                        $stats['controlled']++;
                    } elseif ($incidentStatusId === 5) {
                        $stats['closed']++;
                    }

                    $completedAt = $incident->incident_completed_at;
                    if (! $completedAt && in_array($incidentStatusId, [4, 5], true)) {
                        $completedAt = $incident->updated_at;
                    }
                    $responseMinutes = null;
                    if ($incident->created_at && $completedAt) {
                        $responseMinutes = Carbon::parse($incident->created_at)->diffInMinutes(Carbon::parse($completedAt));
                        $responseDurations[] = $responseMinutes;
                    }

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
                        'location' => $location,
                        'coordinates' => [
                            'lat' => $marker?->lat,
                            'lng' => $marker?->lng,
                        ],
                        'reported_at' => optional($incident->created_at)->toIso8601String(),
                        'updated_at' => optional($incident->updated_at)->toIso8601String(),
                        'created_at' => optional($incident->created_at)->toIso8601String(),
                        'arrival_at' => $arrivalAt?->toIso8601String(),
                        'dispatch_assigned_at' => optional($incident->dispatch_assigned_at)->toIso8601String(),
                        'completed_at' => $completedAt ? Carbon::parse($completedAt)->toIso8601String() : null,
                        'resolved_at' => in_array($incidentStatusId, [4, 5], true)
                            ? ($completedAt ? Carbon::parse($completedAt)->toIso8601String() : optional($incident->updated_at)->toIso8601String())
                            : null,
                        'response_minutes' => $responseMinutes,
                        'reporter_name' => $reporterName !== '' ? $reporterName : 'Concerned Citizen',
                        'status' => $incident->status,
                        'image_url' => $this->normalizeStoredMediaPath($marker?->image),
                        'has_spot_report' => $spotReportId !== null,
                        'spot_report_id' => $spotReportId,
                        'investigation_ready' => $investigationReady,
                        'investigation_url' => $investigationReady
                            ? route('firefighter.my-station', [
                                'openReport' => 'spot',
                                'incident_id' => $incident->incident_id,
                            ])
                            : null,
                    ];
                })->values();

                $stats['avg_response_minutes'] = count($responseDurations) > 0
                    ? round(array_sum($responseDurations) / count($responseDurations), 1)
                    : 0;

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
        );
    }

    protected function buildFirefighterIncidentRegistryDetail(int $userId, int $incidentId): array
    {
        $userTeam = $this->resolveUserTeam($userId);
        abort_unless($userTeam, 403, 'You are not assigned to a firefighter response team.');

        $isAuthorized = Incident::query()
            ->whereKey($incidentId)
            ->where(function ($query) use ($userTeam) {
                $query->where('team_id', $userTeam->team_id)
                    ->orWhereHas('responseUnits', fn ($unitQuery) => $unitQuery->where('team_id', $userTeam->team_id));
            })
            ->exists();
        abort_unless($isAuthorized, 404, 'Incident details not found for your response team.');

        return Cache::remember(
            "firefighter:registry:detail:{$incidentId}",
            now()->addSeconds(self::REGISTRY_DETAIL_CACHE_SECONDS),
            function () use ($userId, $incidentId) {
                $summary = collect($this->buildFirefighterIncidentRegistryData($userId)['incidents'] ?? [])
                    ->first(fn (array $item) => (int) ($item['incident_id'] ?? 0) === $incidentId);

                if (! $summary) {
                    Cache::forget("firefighter:registry:{$userId}");
                    $summary = collect($this->buildFirefighterIncidentRegistryData($userId)['incidents'] ?? [])
                        ->first(fn (array $item) => (int) ($item['incident_id'] ?? 0) === $incidentId);
                }
                abort_unless($summary, 404, 'Incident registry record not found.');

                $incident = Incident::query()
                    ->with([
                        'responderSnapshot:incident_response_id,incident_id,responders',
                        'responseUnits',
                    ])
                    ->findOrFail($incidentId);
                app(IncidentResponderSnapshotService::class)->attachResponderDetails(collect([$incident]));
                $resourceDispatch = app(IncidentResourceDispatchService::class);
                $responseUnits = $incident->responseUnits
                    ->sortBy('run_number')
                    ->map(fn ($unit) => $resourceDispatch->serializeUnit($unit))
                    ->values();

                $unitResponders = $responseUnits
                    ->flatMap(fn (array $unit) => collect($unit['team_members'] ?? [])->map(fn (array $member) => [
                        'station_officer_id' => (int) ($member['station_officer_id'] ?? 0),
                        'name' => $member['name'] ?? 'Assigned firefighter',
                        'role' => $member['role'] ?? null,
                        'rank' => $member['rank'] ?? null,
                        'station_name' => $unit['station_name'] ?? null,
                    ]));
                $snapshotResponders = collect($incident->responder_details ?? [])->map(fn (array $member) => [
                    'station_officer_id' => (int) ($member['station_officer_id'] ?? 0),
                    'name' => $member['name'] ?? 'Assigned firefighter',
                    'role' => null,
                    'rank' => $member['rank'] ?? null,
                    'station_name' => $member['station_name'] ?? null,
                ]);
                $responders = $unitResponders
                    ->concat($snapshotResponders)
                    ->filter(fn (array $member) => $member['station_officer_id'] > 0)
                    ->unique('station_officer_id')
                    ->values()
                    ->all();

                $dispatchedAt = $incident->responseUnits->pluck('dispatched_at')->filter()->sort()->first()
                    ?: $incident->dispatch_assigned_at;
                $arrivalAt = $incident->responseUnits->pluck('arrived_at')->filter()->sort()->first()
                    ?: ($summary['arrival_at'] ?? null);
                $completedAt = $incident->incident_completed_at ?: ($summary['completed_at'] ?? null);
                $statusId = (int) ($summary['incident_status_id'] ?? 0);
                $timeline = collect([
                    ['label' => 'Incident reported', 'timestamp' => $summary['reported_at'] ?? null, 'detail' => $summary['reporter_name'] ?? 'Concerned Citizen'],
                    ['label' => 'Response dispatched', 'timestamp' => $dispatchedAt ? Carbon::parse($dispatchedAt)->toIso8601String() : null, 'detail' => $responders !== [] ? count($responders).' responders dispatched' : 'Response team dispatched'],
                    ['label' => 'Responders arrived', 'timestamp' => $arrivalAt ? Carbon::parse($arrivalAt)->toIso8601String() : null, 'detail' => $arrivalAt ? 'First response unit reached the incident' : null],
                    ['label' => $statusId === 5 ? 'Incident closed' : 'Response completed', 'timestamp' => $completedAt ? Carbon::parse($completedAt)->toIso8601String() : null, 'detail' => isset($summary['response_minutes']) ? $summary['response_minutes'].' minutes after the report' : null],
                ])->filter(fn (array $event) => filled($event['timestamp']))->values()->all();

                return array_merge($summary, [
                    'arrival_at' => $arrivalAt ? Carbon::parse($arrivalAt)->toIso8601String() : null,
                    'dispatch_assigned_at' => $dispatchedAt ? Carbon::parse($dispatchedAt)->toIso8601String() : null,
                    'responders' => $responders,
                    'response_units' => $responseUnits->all(),
                    'timeline' => $timeline,
                ]);
            }
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->user()->user_id;
        $stationOfficer = $this->getStationOfficerForUserId($userId);
        $thisStation = $stationOfficer?->station_id;
        $dashboardAnnouncementStories = $this->loadFirefighterDashboardRecentPosts();

        return view('firefighters.dashboard', compact('thisStation', 'dashboardAnnouncementStories'));
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

    public function myStation()
    {
        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);

        return view('firefighters.mystation', [
            'user' => $context['user'],
            'stationOfficer' => $context['stationOfficer'],
            'station' => $context['station'],
            'stationId' => $context['stationId'],
            'stationAddress' => $context['stationAddress'],
            'stationImageUrl' => $context['stationImageUrl'],
            'currentOfficerName' => $context['currentOfficerName'],
            'activeReportTab' => request('report_tab', 'spot'),
            'proofImageDeleteTemplate' => route('firefighter.investigation-reports.proof-images.destroy', [
                'reportType' => '__REPORT_TYPE__',
                'reportId' => '__REPORT_ID__',
                'imageId' => '__IMAGE_ID__',
            ]),
            'proofImageOrderTemplate' => route('firefighter.investigation-reports.proof-images.order', [
                'reportType' => '__REPORT_TYPE__',
                'reportId' => '__REPORT_ID__',
            ]),
            'finalSubstantiatingDocumentOptions' => [
                'Spot Investigation Report',
                'Progress Investigation Report',
                'Affidavit of loss (itemized and duly notarized)',
                'Sworn statement of loss submitted to insurance adjusters / companies',
                'Latest complete inventory of stocks prior to the fire incident',
                'Complete inventory of salvaged items after the fire incident',
                'Complete copies of insurance policies to include co-insurances',
                'Sworn statements of witnesses',
                'Income Tax Return (ITR) for the last three (3) years',
                'Financial statements for the last three (3) years',
                'Balance sheets for the last three (3) years',
                'Mayor\'s permit and Business license',
                'Occupancy permit',
                'Department of Trade and Industry (DTI) registration',
                'Securities and Exchange Commission (SEC) registration',
                'Latest Fire Safety Inspection Certificate (FSIC)',
                'Complete list of employees',
                'Approved Floor, Building and Electrical Plans',
                'Copy of lease contract agreement',
                'Land title / tax declaration',
                'Fire Laboratory Services Report',
                'Photographs of the fire scene',
            ],
        ]);
    }

    public function myStationOverviewData()
    {
        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);
        $station = Station::query()
            ->select(['station_id'])
            ->with(['galleryMedia:station_media_id,station_id,path,caption'])
            ->where('station_id', $context['stationId'])
            ->first();

        $stationGallery = collect($station?->galleryMedia ?? [])
            ->map(fn ($media) => [
                'id' => $media->station_media_id,
                'caption' => $media->caption,
                'url' => MediaPath::url($media->path),
            ])
            ->values();

        $userTeam = $this->resolveUserTeam($userId, $context['stationOfficer']);
        $stationOfficerIds = $this->getStationOfficerIdsForStation($context['stationId']);
        $counts = $this->getStationOverviewCounts($context['stationId'], $userTeam, $stationOfficerIds);

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => $counts,
                'html' => view('firefighters.mystation-overview', [
                    'stationGallery' => $stationGallery,
                    'counts' => $counts,
                ])->render(),
            ],
        ]);
    }

    public function myStationPersonnelData()
    {
        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);
        $stationOfficers = $this->loadStationOfficerRoster($context['stationId']);
        $userTeam = $this->resolveUserTeam($userId, $context['stationOfficer']);
        $crewMembers = $this->loadCrewMembersForTeam($userTeam);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('firefighters.mystation-personnel', [
                    'stationOfficers' => $stationOfficers,
                    'crewMembers' => $crewMembers,
                ])->render(),
            ],
        ]);
    }

    public function myStationEquipmentData()
    {
        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);
        $equipmentAssignments = $this->loadStationEquipmentAssignments($context['stationId']);
        $apparatusUnits = $this->loadStationApparatus($context['stationId']);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => view('firefighters.mystation-equipment', [
                    'equipmentAssignments' => $equipmentAssignments,
                    'apparatusUnits' => $apparatusUnits,
                ])->render(),
            ],
        ]);
    }

    public function myStationReportsData(Request $request)
    {
        $tab = $request->validate([
            'tab' => ['nullable', Rule::in(['spot', 'progress', 'final'])],
        ])['tab'] ?? 'spot';

        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);
        $stationOfficerIds = $this->getStationOfficerIdsForStation($context['stationId']);
        $currentOfficerName = $context['currentOfficerName'];

        if ($tab === 'progress') {
            $reports = $this->loadProgressReportsPaginator($stationOfficerIds);
            $store = $this->mapProgressReportStore($reports->items(), $currentOfficerName);
        } elseif ($tab === 'final') {
            $reports = $this->loadFinalReportsPaginator($stationOfficerIds);
            $store = $this->mapFinalReportStore($reports->items(), $currentOfficerName);
        } else {
            $reports = $this->loadSpotReportsPaginator($stationOfficerIds);
            $store = $this->mapSpotReportStore($reports->items(), $currentOfficerName);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'tab' => $tab,
                'store' => $store,
                'html' => view('firefighters.mystation-reports-table', [
                    'tab' => $tab,
                    'reports' => $reports,
                ])->render(),
            ],
        ]);
    }

    public function myStationModalData(Request $request, BoundaryMatcherService $boundaryMatcherService)
    {
        $scope = $request->validate([
            'scope' => ['required', Rule::in(['spot', 'progress', 'final', 'common'])],
        ])['scope'];

        $userId = Auth::user()->user_id;
        $context = $this->getStationShellContext($userId);
        $stationOfficerIds = $this->getStationOfficerIdsForStation($context['stationId']);
        $stationOfficers = $this->loadStationOfficerRoster($context['stationId']);

        $payload = [
            'officers' => $this->buildOfficerOptions($stationOfficers),
        ];

        if ($scope === 'spot') {
            $stationTeamIds = $this->getStationTeamIdsForOfficerIds($stationOfficerIds);
            $payload['incidents'] = $this->buildSpotIncidentOptions(
                $this->loadAvailableSpotIncidents($stationTeamIds, $boundaryMatcherService)
            );
        }

        if ($scope === 'progress') {
            $availableSpotReports = SpotInvestigationReport::with(['incident.marker', 'incident.barangay.city'])
                ->whereDoesntHave('progressInvestigation')
                ->whereIn('station_officer_id', $stationOfficerIds)
                ->latest()
                ->get();

            $payload['spot_reports'] = $this->buildSpotReportOptions($availableSpotReports);
            $payload['fire_origins'] = $this->buildFireOriginOptions();
        }

        if ($scope === 'final') {
            $availableProgressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'spotInvestigation.incident.barangay.city'])
                ->whereDoesntHave('finalInvestigation')
                ->whereIn('station_officer_id', $stationOfficerIds)
                ->latest()
                ->get();

            $payload['progress_reports'] = $this->buildProgressReportOptions($availableProgressReports);
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function schedule()
    {
        $userId = Auth::user()->user_id;
        $stationOfficer = $this->getStationOfficerForUserId($userId, true);
        $stationId = $stationOfficer?->station_id;
        $stationName = $stationOfficer?->station?->station_name;

        return view('firefighters.schedule', compact(
            'stationOfficer',
            'stationId',
            'stationName'
        ));
    }

    public function dashboardData()
    {
        $userId = Auth::user()->user_id;

        return response()->json([
            'success' => true,
            'data' => $this->buildFirefighterDashboardWidgetPayload($userId),
        ]);
    }

    public function dashboardPostsData()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $this->loadFirefighterDashboardRecentPosts(),
            ],
        ]);
    }

    public function scheduleData()
    {
        $userId = Auth::user()->user_id;

        return response()->json([
            'success' => true,
            'data' => $this->buildFirefighterSchedulePayload($userId),
        ]);
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

    $validated = $request->validateWithBag('profileUpdate', [
        'first_name' => ['required', 'string', 'max:255'],
        'middle_name' => ['nullable', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'contact_number' => ['required', 'string', 'max:30'],
    ]);

    $user->update($validated);
    UserActivityLogger::log('updated profile', Auth::user()?->user_id, $request);

    return redirect()
        ->route('firefighter.my-profile')
        ->with('success', 'Profile updated.');
}

public function updateMyProfilePhoto(Request $request, ProfileImageService $profileImages)
{
    $user = User::where('user_id', Auth::user()->user_id)->firstOrFail();

    $validated = $request->validate([
        'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    $user = $profileImages->replace($user, $validated['profile_image']);

    UserActivityLogger::log('updated firefighter profile photo', Auth::user()?->user_id, $request);

    return response()->json([
        'success' => true,
        'message' => 'Profile photo updated successfully.',
        'user' => [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Firefighter',
            'initials' => strtoupper(substr($user->first_name ?? 'F', 0, 1) . substr($user->last_name ?? 'R', 0, 1)),
            'image_url' => $user->profile_image_url,
            'thumbnail_url' => $user->profile_thumbnail_url,
        ],
    ]);
}

public function updateMyProfileEmail(Request $request)
{
    $user = User::where('user_id', Auth::user()->user_id)->firstOrFail();

    $validated = $request->validateWithBag('emailUpdate', [
        'new_email' => [
            'required',
            'email',
            'max:255',
            'different:current_email',
            Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
        ],
        'current_password' => ['required', 'current_password'],
    ], [
        'new_email.different' => 'Please enter a different email address.',
        'current_password.current_password' => 'The current password is incorrect.',
    ]);

    $user->update([
        'email' => strtolower($validated['new_email']),
    ]);

    UserActivityLogger::log('updated profile email', Auth::user()?->user_id, $request);

    return redirect()
        ->route('firefighter.my-profile')
        ->with('success', 'Email address updated.');
}

public function updateMyProfilePassword(Request $request)
{
    $user = User::where('user_id', Auth::user()->user_id)->firstOrFail();

    $validated = $request->validateWithBag('passwordUpdate', [
        'current_password' => ['required', 'current_password'],
        'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
    ], [
        'current_password.current_password' => 'The current password is incorrect.',
        'password.different' => 'Use a new password that is different from your current password.',
    ]);

    $user->forceFill([
        'password' => $validated['password'],
    ])->save();

    UserActivityLogger::log('updated profile password', Auth::user()?->user_id, $request);

    return redirect()
        ->route('firefighter.my-profile')
        ->with('success', 'Password updated successfully.');
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
            
            $locations = Cache::remember(
                'firefighter:locations:active',
                now()->addSeconds(self::ACTIVE_FIREFIGHTER_LOCATIONS_CACHE_SECONDS),
                function () use ($userLocationTable) {
                    return DB::table($userLocationTable)
                        ->join('users', $userLocationTable . '.user_id', '=', 'users.user_id')
                        ->where($userLocationTable . '.updated_at', '>=', Carbon::now()->subMinutes(5))
                        ->select([
                            $userLocationTable . '.user_id',
                            $userLocationTable . '.latitude',
                            $userLocationTable . '.longitude',
                            $userLocationTable . '.updated_at',
                            'users.first_name',
                            'users.last_name',
                        ])
                        ->get();
                }
            );
            
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $resolvedUserId = Auth::id();

        if (! $resolvedUserId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to resolve the current user for location tracking.'
            ], 422);
        }

        $locationSignature = round((float) $validated['latitude'], 6) . ':' . round((float) $validated['longitude'], 6);
        $locationCacheKey = "firefighter:location:last-write:{$resolvedUserId}";
        $lastLocationSignature = Cache::get($locationCacheKey);

        if ($lastLocationSignature === $locationSignature) {
            return response()->json([
                'success' => true,
                'message' => 'Location already current'
            ]);
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

        Cache::put($locationCacheKey, $locationSignature, now()->addSeconds(90));
        Cache::forget('firefighter:locations:active');
        
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
        $cacheKey = "firefighter:assigned:{$id}";
        $incidentCoords = Cache::remember($cacheKey, now()->addSeconds(self::ASSIGNED_INCIDENTS_CACHE_SECONDS), function () use ($id) {
            $userTeam = $this->resolveUserTeam((int) $id);
            if (! $userTeam) {
                return [];
            }

            return $this->loadTeamIncidentsForRegistry((int) $userTeam->team_id, true)
                ->map(fn (Incident $incident) => $this->transformAssignedIncident($incident))
                ->values()
                ->all();
        });

        return response()->json(['success'=> true, 'data'=>$incidentCoords]);
    }

    public function incidentView(){
        $currentUserId = Auth::user()->user_id;
        $stationOfficer = $this->getStationOfficerForUserId($currentUserId, true);
        $userTeam = $this->resolveUserTeam($currentUserId);

        return view('firefighters.incidents', [
            'stationOfficer' => $stationOfficer ? [
                'station_officer_id' => $stationOfficer->station_officer_id,
                'station_name' => $stationOfficer->station?->station_name,
                'rank' => $stationOfficer->rank,
            ] : null,
            'userTeam' => $userTeam ? [
                'team_id' => $userTeam->team_id,
                'team_name' => $userTeam->team_name ?? ('Team #' . $userTeam->team_id),
            ] : null,
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

    public function incidentRegistryDetail(Incident $incident)
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildFirefighterIncidentRegistryDetail(
                (int) Auth::user()->user_id,
                (int) $incident->incident_id
            ),
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

    protected function buildFirefighterDashboardMapPayload(int $userId, ?Team $userTeam = null, ?StationOfficer $stationOfficer = null): array
    {
        $resolvedTeam = $userTeam ?: $this->resolveUserTeam($userId);
        $resolvedStationOfficer = $stationOfficer ?: $this->getStationOfficerForUserId($userId);

        return Cache::remember(
            "firefighter:dashboard:map-payload:{$userId}",
            now()->addSeconds(self::DASHBOARD_CACHE_SECONDS),
            function () use ($resolvedTeam, $resolvedStationOfficer) {
                $incidentCoords = collect();

                if ($resolvedTeam) {
                    $incidentCoords = $this->loadTeamIncidentsForRegistry((int) $resolvedTeam->team_id, true);
                }

                $viewer = User::query()
                    ->with('role')
                    ->find($userId);

                $allMarkers = MarkerFeedBuilder::buildForViewer($viewer)->values();

                $stations = \App\Models\Station::query()
                    ->select(['station_id', 'station_name', 'marker_id'])
                    ->with(['marker:marker_id,title,description,lat,lng,status,category,image'])
                    ->get();

                return [
                    'incidentCoords' => $incidentCoords,
                    'incidents' => $incidentCoords,
                    'allMarkers' => $allMarkers,
                    'stations' => $stations,
                    'posts' => collect(),
                    'thisStation' => $resolvedStationOfficer?->station_id,
                    'thiscrew' => collect(),
                ];
            }
        );
    }

    protected function buildFirefighterDashboardWidgetPayload(int $userId): array
    {
        return Cache::remember(
            "firefighter:dashboard:widgets:{$userId}",
            now()->addSeconds(self::DASHBOARD_CACHE_SECONDS),
            function () use ($userId) {
                $stationOfficer = $this->getStationOfficerForUserId($userId);
                $userTeam = $this->resolveUserTeam($userId, $stationOfficer);

                $thisStation = $stationOfficer?->station_id;
                $thiscrew = $thisStation
                    ? StationOfficer::query()
                        ->select([
                            'station_officers.station_officer_id',
                            'station_officers.station_id',
                            'station_officers.user_id',
                            'station_officers.rank',
                            'users.first_name',
                            'users.last_name',
                        ])
                        ->join('users', 'users.user_id', '=', 'station_officers.user_id')
                        ->where('station_officers.station_id', $thisStation)
                        ->get()
                    : collect();

                $activeIncidentCount = $userTeam
                    ? Incident::query()
                        ->where('team_id', $userTeam->team_id)
                        ->whereNotIn('status', [self::STATUS_DB_CONTROLLED, self::STATUS_DB_CLOSED])
                        ->count()
                    : 0;

                $stations = Cache::remember(
                    'firefighter:dashboard:nearby-stations:reference',
                    now()->addSeconds(self::DASHBOARD_CACHE_SECONDS),
                    fn () => Station::query()
                        ->leftJoin('markers', 'markers.marker_id', '=', 'stations.marker_id')
                        ->select([
                            'stations.station_id',
                            'stations.station_name',
                            'stations.marker_id',
                            'markers.lat as marker_lat',
                            'markers.lng as marker_lng',
                        ])
                        ->get()
                );

                $currentStation = $stations->firstWhere('station_id', $thisStation);
                $originLat = $currentStation?->marker_lat;
                $originLng = $currentStation?->marker_lng;

                if ($originLat !== null && $originLng !== null) {
                    $stations = $stations
                        ->filter(function ($station) use ($thisStation) {
                            return $station->station_id != $thisStation
                                && $station->marker_lat !== null
                                && $station->marker_lng !== null;
                        })
                        ->map(function ($station) use ($originLat, $originLng) {
                            $station->distance = $this->haversineDistance(
                                (float) $originLat,
                                (float) $originLng,
                                (float) $station->marker_lat,
                                (float) $station->marker_lng
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
                    'context' => [
                        'station_id' => $thisStation,
                        'user_team_id' => $userTeam?->team_id,
                    ],
                    'counts' => [
                        'active_incidents' => $activeIncidentCount,
                        'equipment' => 12,
                        'crew_online' => $thiscrew->count(),
                    ],
                    'stations' => $stations->map(function (Station $station) {
                        return [
                            'station_name' => $station->station_name,
                            'distance' => number_format((float) ($station->distance ?? 0), 1),
                        ];
                    })->values(),
                    'crew' => $thiscrew->map(function ($crew) {
                        return [
                            'initials' => substr($crew->first_name ?? 'F', 0, 1) . substr($crew->last_name ?? 'F', 0, 1),
                            'name' => trim(($crew->first_name ?? ' ') . ' ' . ($crew->last_name ?? 'Firefighter')),
                            'role' => ($crew->rank ?? 'Firefighter') . ' · Station ' . $crew->station_id,
                            'status' => 'online',
                        ];
                    })->values(),
                ];
            }
        );
    }

    protected function loadFirefighterDashboardRecentPosts(): array
    {
        return Cache::remember(
            Post::FIREFIGHTER_DASHBOARD_RECENT_POSTS_CACHE_KEY,
            now()->addSeconds(self::DASHBOARD_CACHE_SECONDS),
            function () {
                return Post::query()
                    ->select(['id', 'user_id', 'content', 'images', 'likes_count', 'comments_count', 'created_at'])
                    ->with(['user:user_id,first_name,last_name'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function (Post $post) {
                        $firstImage = collect($post->images ?? [])->filter()->first();
                        return [
                            'id' => $post->id,
                            'author_initials' => substr($post->user->first_name ?? 'A', 0, 1) . substr($post->user->last_name ?? 'D', 0, 1),
                            'author_name' => trim(($post->user->first_name ?? 'Admin') . ' ' . ($post->user->last_name ?? 'Admin')),
                            'time' => optional($post->created_at)->diffForHumans(),
                            'content' => $post->content,
                            'image_url' => $firstImage ? MediaPath::url($firstImage) : null,
                            'likes_count' => $post->likes_count ?? 0,
                            'comments_count' => $post->comments_count ?? 0,
                        ];
                    })
                    ->values()
                    ->all();
            }
        );
    }

    protected function buildFirefighterSchedulePayload(int $userId): array
    {
        $stationOfficer = $this->getStationOfficerForUserId($userId, true);
        $stationId = $stationOfficer?->station_id;
        $stationName = $stationOfficer?->station?->station_name;

        $scheduleEvents = EventActivity::query()
            ->where(function ($query) use ($stationId, $userId) {
                if ($stationId) {
                    $query->where(function ($stationQuery) use ($stationId) {
                        $stationQuery->where('station_id', $stationId)->whereNull('user_id');
                    });
                }
                $query->orWhere('user_id', $userId);
            })
            ->orderBy('event_date')->orderBy('start_time')->get()
            ->map(fn (EventActivity $event) => $this->serializeScheduleEvent($event, $userId));

        $dutyEvents = collect();
        if ($stationId && $stationOfficer) {
            $from = now()->subMonths(6)->startOfDay();
            $to = now()->addYear()->endOfDay();
            app(DutyRosterRecurrenceService::class)->generate((int) $stationId, $from, $to);
            $exceptions = DutyException::query()
                ->where('station_officer_id', $stationOfficer->station_officer_id)
                ->where('starts_at', '<', $to)->where('ends_at', '>', $from)->get();
            $dutyEvents = DutyShift::query()
                ->with(['assignments' => fn ($query) => $query->where('station_officer_id', $stationOfficer->station_officer_id), 'team.station', 'template'])
                ->where('station_id', $stationId)->where('status', 'scheduled')
                ->where('starts_at', '<', $to)->where('ends_at', '>', $from)
                ->whereHas('assignments', fn ($query) => $query->where('station_officer_id', $stationOfficer->station_officer_id))
                ->orderBy('starts_at')->get()
                ->map(function (DutyShift $shift) use ($stationOfficer, $exceptions) {
                    $exception = $exceptions->first(fn (DutyException $item) => $item->starts_at->lt($shift->ends_at) && $item->ends_at->gt($shift->starts_at));
                    return $this->serializeDutyShift($shift, (int) $stationOfficer->station_officer_id, $exception);
                });
        }

        return [
            'station_id' => $stationId,
            'station_name' => $stationName ?: 'Unassigned Station',
            'events' => $scheduleEvents->concat($dutyEvents)->sortBy(fn ($event) => ($event['event_date'] ?? '').' '.($event['start_time'] ?? ''))->values(),
        ];
    }
}
