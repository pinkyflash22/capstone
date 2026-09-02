<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Marker;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\Team;
use App\Models\StationOfficer;
use App\Models\Station;
use App\Models\User;
use App\Services\BoundaryMatcherService;
use App\Support\IncidentContextDispatcher;
use App\Support\IncidentFollowUpDispatcher;
use App\Support\UserActivityLogger;
use App\Services\IncidentAutoAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Exceptions\HttpResponseException;

class IncidentController extends Controller
{
    private const STATUS_REPORTED = 1;
    private const STATUS_RESPONDING = 2;
    private const STATUS_ON_SCENE = 3;
    private const STATUS_CONTROLLED = 4;
    private const STATUS_CLOSED = 5;
    private const TYPE_FALSE_ALARM = 5;
    private const STATUS_DB_REPORTED = 'Reported';
    private const STATUS_DB_RESPONDING = 'Responding';
    private const STATUS_DB_ON_SCENE = 'On Scene';
    private const STATUS_DB_CONTROLLED = 'Controlled';
    private const STATUS_DB_CLOSED = 'Closed';
    private const STATUS_NAME_REPORTED = 'reported';
    private const STATUS_NAME_RESPONDING = 'en-route';
    private const STATUS_NAME_ON_SCENE = 'on-scene';
    private const STATUS_NAME_CONTROLLED = 'controlled';
    private const STATUS_NAME_CLOSED = 'closed';
    private const LATEST_INCIDENTS_CACHE_SECONDS = 10;

    private function resolveIncidentTypeLabel(?Incident $incident): string
    {
        $markerCategory = strtolower(trim((string) ($incident?->marker?->category ?? '')));
        if ($markerCategory === 'fire') {
            return 'Fire';
        }

        return 'Incident';
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

    private function resolveIncidentStatusLabel(?Incident $incident): string
    {
        return $this->normalizeStatusName($incident?->status);
    }

    private function findRecentDuplicateIncident(float $lat, float $lng, int $minutes = 10, float $distanceThresholdMeters = 200): ?Incident
    {
        $recentIncidents = Incident::with(['marker'])
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereIn('status', [self::STATUS_DB_REPORTED, 'Responding', 'On Scene'])
            ->when(Schema::hasColumn('incidents', 'duplicate_of_incident_id'), fn ($query) => $query->whereNull('duplicate_of_incident_id'))
            ->orderByDesc('created_at')
            ->get();

        foreach ($recentIncidents as $recentIncident) {
            $marker = $recentIncident->marker;
            if (! $marker || $marker->lat === null || $marker->lng === null) {
                continue;
            }

            $distanceMeters = $this->calculateDistanceMeters(
                $lat,
                $lng,
                (float) $marker->lat,
                (float) $marker->lng
            );

            if ($distanceMeters <= $distanceThresholdMeters) {
                return $recentIncident;
            }
        }

        return null;
    }

    private function normalizeStatusName(?string $statusName): string
    {
        return match (strtolower(trim((string) $statusName))) {
            'responding', 'en-route', 'en route' => self::STATUS_NAME_RESPONDING,
            'on scene', 'on-scene' => self::STATUS_NAME_ON_SCENE,
            'controlled', 'completed' => self::STATUS_NAME_CONTROLLED,
            'closed' => self::STATUS_NAME_CLOSED,
            default => self::STATUS_NAME_REPORTED,
        };
    }

    private function mapStatusIdToName(int $statusId): string
    {
        return match ($statusId) {
            self::STATUS_RESPONDING => self::STATUS_DB_RESPONDING,
            self::STATUS_ON_SCENE => self::STATUS_DB_ON_SCENE,
            self::STATUS_CONTROLLED => self::STATUS_DB_CONTROLLED,
            self::STATUS_CLOSED => self::STATUS_DB_CLOSED,
            self::STATUS_REPORTED => self::STATUS_DB_REPORTED,
            default => self::STATUS_DB_REPORTED,
        };
    }

    private function resolveDatabaseStatusName(?string $statusName): string
    {
        return $this->mapStatusIdToName($this->mapStatusNameToId($statusName));
    }

    private function mapStatusNameToId(?string $statusName): int
    {
        return match ($this->normalizeStatusName($statusName)) {
            self::STATUS_NAME_RESPONDING => self::STATUS_RESPONDING,
            self::STATUS_NAME_ON_SCENE => self::STATUS_ON_SCENE,
            self::STATUS_NAME_CONTROLLED => self::STATUS_CONTROLLED,
            self::STATUS_NAME_CLOSED => self::STATUS_CLOSED,
            default => self::STATUS_REPORTED,
        };
    }

    private function getIncidentRelations(): array
    {
        return ['marker', 'team', 'station', 'reporter', 'barangay.city', 'responderSnapshot', 'incidentReport'];
    }

    private function supportsBarangayResolution(): bool
    {
        return Cache::rememberForever('schema:incident:barangay-resolution', function () {
            return Schema::hasColumn('incidents', 'barangay_id')
                && Schema::hasColumn('incidents', 'barangay_resolution_status')
                && Schema::hasColumn('incidents', 'barangay_resolution_source')
                && Schema::hasColumn('incidents', 'barangay_resolved_at');
        });
    }

    private function supportsIncidentStationColumn(): bool
    {
        return Cache::rememberForever('schema:incident:station-id', function () {
            return Schema::hasColumn('incidents', 'station_id');
        });
    }

    private function serializeIncident(Incident $incident): array
    {
        $dispatch = app(\App\Services\IncidentDispatchStateService::class)->describe($incident);

        return [
            ...$incident->toArray(),
            ...$dispatch,
            'incident_type' => $this->resolveIncidentTypeLabel($incident),
            'incident_type_name' => $this->resolveIncidentTypeLabel($incident),
            'incident_type_id' => null,
            'incident_status' => $this->resolveIncidentStatusLabel($incident),
            'incident_status_id' => $this->mapStatusNameToId($incident->status),
            'barangay_resolution_status' => $this->supportsBarangayResolution()
                ? ($incident->barangay_resolution_status ?? 'pending')
                : 'pending',
            'barangay_resolution_source' => $this->supportsBarangayResolution()
                ? $incident->barangay_resolution_source
                : null,
            'barangay_resolved_at' => $this->supportsBarangayResolution()
                ? optional($incident->barangay_resolved_at)->toISOString()
                : null,
            'barangay_name' => $this->supportsBarangayResolution() ? $incident->barangay?->barangay_name : null,
            'barangay_city_name' => $this->supportsBarangayResolution() ? $incident->barangay?->city?->city_name : null,
        ];
    }

    private function supportsLegacyIncidentStatus(): bool
    {
        return Schema::hasColumn('incidents', 'incident_status_id') && Schema::hasTable('incident_status');
    }

    private function supportsLegacyIncidentType(): bool
    {
        return Schema::hasColumn('incidents', 'incident_type_id') && Schema::hasTable('incident_type');
    }

    private function getTeamMemberStationOfficerIds(?Team $team)
    {
        if (!$team) {
            return collect();
        }

        return collect([
            $team->commander_driver,
            $team->pumper,
            $team->team_leader,
            $team->firefighter_officer_1,
            $team->firefighter_officer_2,
            $team->firefighter_officer_3,
            $team->rescue_radio,
        ])->filter()->unique()->values();
    }

    private function syncTeamAndStationOfficerStatuses(Incident $incident, int $statusId): void
    {
        $resourceDispatch = app(\App\Services\IncidentResourceDispatchService::class);
        if ($statusId === self::STATUS_ON_SCENE) {
            $resourceDispatch->markIncidentUnitsOnScene($incident);
        } elseif (in_array($statusId, [self::STATUS_CONTROLLED, self::STATUS_CLOSED], true)) {
            $resourceDispatch->releaseIncidentUnits($incident);
        }

        if (!$incident->team_id) {
            return;
        }

        $team = Team::find($incident->team_id);
        if (!$team) {
            return;
        }

        $teamStatus = 'available';
        $stationOfficerStatus = null;

        switch ($statusId) {
            case self::STATUS_RESPONDING:
                $teamStatus = 'responding';
                $stationOfficerStatus = 'dispatched';
                break;
            case self::STATUS_ON_SCENE:
                $teamStatus = 'responding';
                $stationOfficerStatus = 'on-scene';
                break;
            case self::STATUS_CONTROLLED:
            case self::STATUS_CLOSED:
                $teamStatus = 'available';
                $stationOfficerStatus = 'available';
                break;
            case self::STATUS_REPORTED:
            default:
                $teamStatus = 'available';
                break;
        }

        $team->update([
            'status' => $teamStatus,
            'updated_at' => now()
        ]);

        if (in_array($statusId, [self::STATUS_CONTROLLED, self::STATUS_CLOSED], true) && $team->roster_managed) {
            app(\App\Services\DutyRosterService::class)->syncTeam($team->fresh());
            $team->refresh();
        }

        $teamMemberStationOfficerIds = $this->getTeamMemberStationOfficerIds($team);
        if ($stationOfficerStatus && $teamMemberStationOfficerIds->isNotEmpty()) {
            StationOfficer::whereIn('station_officer_id', $teamMemberStationOfficerIds->all())
                ->update([
                    'status' => $stationOfficerStatus,
                    'updated_at' => now()
                ]);
        }

        if (in_array($statusId, [self::STATUS_CONTROLLED, self::STATUS_CLOSED], true)) {
            try {
                app(IncidentAutoAssignmentService::class)->assignRecentUnassignedIncidents(60);
            } catch (\Throwable $e) {
                Log::error('Failed to auto-assign unassigned incidents after team became available: ' . $e->getMessage(), [
                    'incident_id' => $incident->incident_id,
                    'team_id' => $incident->team_id,
                ]);
            }
        }
    }

    private function getStationsSortedByDistance(float $lat, float $lng)
    {
        return Station::with('marker')
            ->get()
            ->filter(function ($station) {
                return $station->marker && $station->marker->lat !== null && $station->marker->lng !== null;
            })
            ->map(function ($station) use ($lat, $lng) {
                $station->distance_meters = $this->calculateDistanceMeters(
                    $lat,
                    $lng,
                    (float) $station->marker->lat,
                    (float) $station->marker->lng
                );

                return $station;
            })
            ->sortBy('distance_meters')
            ->values();
    }

    private function getTeamsGroupedByStation(array $allowedStatuses = ['available'])
    {
        $teams = Team::whereIn('status', $allowedStatuses)->get();
        $leaderIds = $teams->pluck('team_leader')->filter()->unique()->values();
        $stationOfficerMap = StationOfficer::whereIn('station_officer_id', $leaderIds)
            ->pluck('station_id', 'station_officer_id');

        return $teams
            ->map(function ($team) use ($stationOfficerMap) {
                $team->resolved_station_id = $stationOfficerMap[$team->team_leader] ?? null;
                return $team;
            })
            ->filter(function ($team) {
                return !empty($team->resolved_station_id);
            })
            ->groupBy(function ($team) {
                return (int) $team->resolved_station_id;
            })
            ->map(function ($group) {
                return $group->values();
            });
    }

    private function findAvailableTeamForStation(int $stationId): ?Team
    {
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();

        if ($stationOfficerIds->isEmpty()) {
            return null;
        }

        return Team::where('status', 'available')
            ->whereIn('team_leader', $stationOfficerIds->all())
            ->lockForUpdate()
            ->orderBy('team_id')
            ->first();
    }

    private function findAnyTeamForStation(int $stationId): ?Team
    {
        $stationOfficerIds = StationOfficer::where('station_id', $stationId)
            ->pluck('station_officer_id')
            ->filter()
            ->values();

        if ($stationOfficerIds->isEmpty()) {
            return null;
        }

        return Team::whereIn('team_leader', $stationOfficerIds->all())
            ->orderBy('team_id')
            ->first();
    }

    private function getCandidateStationsForIncident(Incident $incident, float $lat, float $lng)
    {
        $stationLimit = strtolower((string) ($incident->marker?->category ?? '')) === 'fire' ? 3 : 1;
        $nearbyStations = $this->getStationsSortedByDistance($lat, $lng);
        $primaryStationId = (int) ($incident->station_id ?? 0);
        $orderedStations = collect();

        if ($primaryStationId > 0) {
            $primaryStation = Station::with('marker')->find($primaryStationId);
            if ($primaryStation) {
                if ($primaryStation->marker && $primaryStation->marker->lat !== null && $primaryStation->marker->lng !== null) {
                    $primaryStation->distance_meters = $this->calculateDistanceMeters(
                        $lat,
                        $lng,
                        (float) $primaryStation->marker->lat,
                        (float) $primaryStation->marker->lng
                    );
                } else {
                    $primaryStation->distance_meters = 0;
                }

                $orderedStations->push($primaryStation);
            }
        }

        return $orderedStations
            ->concat($nearbyStations->reject(fn ($station) => (int) $station->station_id === $primaryStationId))
            ->unique(fn ($station) => (int) $station->station_id)
            ->take($stationLimit)
            ->values();
    }

    private function markTeamResponding(Team $team): void
    {
        $team->status = 'responding';
        $team->updated_at = now();
        $team->save();

        $teamMemberStationOfficerIds = $this->getTeamMemberStationOfficerIds($team);
        if ($teamMemberStationOfficerIds->isNotEmpty()) {
            StationOfficer::whereIn('station_officer_id', $teamMemberStationOfficerIds->all())
                ->update([
                    'status' => 'dispatched',
                    'updated_at' => now()
                ]);
        }
    }

    private function processRecentUnassignedIncidents(int $minutes = 60): array
    {
        $cutoff = now()->subMinutes($minutes);

        $incidents = Incident::with('marker')
            ->whereNull('team_id')
            ->where('status', self::STATUS_DB_REPORTED)
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at', 'asc')
            ->get();

        $assignedIncident = null;

        foreach ($incidents as $incident) {
            $marker = $incident->marker;

            if (!$marker || $marker->lat === null || $marker->lng === null) {
                continue;
            }

            $nearestStations = $this->getCandidateStationsForIncident(
                $incident,
                (float) $marker->lat,
                (float) $marker->lng
            );

            foreach ($nearestStations as $station) {
                $stationId = (int) $station->station_id;
                $team = $this->findAvailableTeamForStation($stationId);

                if (!$team) {
                    continue;
                }

                $incident->team_id = $team->team_id;
                $incident->status = self::STATUS_DB_RESPONDING;
                $incident->updated_at = now();
                $incident->save();

                $this->markTeamResponding($team);

                $assignedIncident = [
                    'incident_id' => $incident->incident_id,
                    'team_id' => $team->team_id,
                    'station_id' => $stationId,
                    'status' => self::STATUS_NAME_RESPONDING,
                    'team_status_updated' => true,
                    'created_at' => optional($incident->created_at)->toISOString(),
                ];

                break;
            }

            if ($assignedIncident) {
                break;
            }
        }

        return [
            'checked' => $incidents->count(),
            'assigned' => $assignedIncident ? [$assignedIncident] : [],
        ];
    }

    private function processSingleIncidentAssignment(Incident $incident): ?array
    {
        return DB::transaction(function () use ($incident) {
            $lockedIncident = Incident::with('marker')
                ->whereKey($incident->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedIncident) {
                return null;
            }

            if ($lockedIncident->team_id || $lockedIncident->status !== self::STATUS_DB_REPORTED) {
                return null;
            }

            $marker = $lockedIncident->marker;
            if (! $marker || $marker->lat === null || $marker->lng === null) {
                return null;
            }

            $nearestStations = $this->getCandidateStationsForIncident(
                $lockedIncident,
                (float) $marker->lat,
                (float) $marker->lng
            );

            foreach ($nearestStations as $station) {
                $stationId = (int) $station->station_id;
                $team = $this->findAvailableTeamForStation($stationId);

                if (! $team) {
                    continue;
                }

                $lockedIncident->team_id = $team->team_id;
                $lockedIncident->status = self::STATUS_DB_RESPONDING;
                $lockedIncident->updated_at = now();
                $lockedIncident->save();

                $this->markTeamResponding($team);

                return [
                    'incident_id' => $lockedIncident->incident_id,
                    'team_id' => $team->team_id,
                    'station_id' => $stationId,
                    'status' => self::STATUS_NAME_RESPONDING,
                    'team_status_updated' => true,
                    'created_at' => optional($lockedIncident->created_at)->toISOString(),
                ];
            }

            return null;
        });
    }

    public function index()
    {
        $incidents = Incident::with($this->getIncidentRelations())->get();
            // ->orderBy('created_at', 'desc')
            // ->paginate(15);
        return view('firefighters.incidents', compact('incidents'));
    }


    public function create()
    {
        $markers = Marker::all();
        $incidentStatuses = $this->supportsLegacyIncidentStatus() ? IncidentStatus::all() : collect();
        $incidentTypes = $this->supportsLegacyIncidentType() ? IncidentType::all() : collect();
        $teams = Team::all();
        $reporters = User::all();
        
        return view('admin.incidents.create', compact('markers', 'incidentStatuses', 
            'incidentTypes', 'teams', 'reporters'));
    }

    public function store(Request $request, BoundaryMatcherService $boundaryMatcherService)
    {
        $validated = $request->validate([
            'marker_id' => 'nullable|exists:markers,marker_id',
            'lat' => 'required_without:marker_id|numeric|between:-90,90',
            'lng' => 'required_without:marker_id|numeric|between:-180,180',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:reported,en-route,on-scene,controlled,closed,completed,Reported,Responding,On Scene,Controlled,Closed',
            'occurred_at' => 'nullable|date',
            'station_id' => 'nullable|exists:stations,station_id',
            'team_id' => 'nullable|exists:teams,team_id',
            'reported_by' => 'required|exists:users,user_id',
            'dispatch_priority' => 'nullable|in:low,medium,high,critical',
            'location_accuracy' => 'nullable|numeric|min:1|max:100',
            'location_captured_at' => 'nullable|date|after_or_equal:'.now()->subMinute()->toIso8601String().'|before_or_equal:'.now()->addMinute()->toIso8601String(),
        ]);

        $markerId = $validated['marker_id'] ?? null;

        $data = DB::transaction(function () use ($validated, $markerId, $boundaryMatcherService) {
            $createdMarker = null;

            if (! $markerId) {
                $lat = (float) $validated['lat'];
                $lng = (float) $validated['lng'];

                $duplicateIncident = $this->findRecentDuplicateIncident($lat, $lng);
                if ($duplicateIncident) {
                    if (Schema::hasColumn('incidents', 'duplicate_of_incident_id')) {
                        $validated['marker_id'] = $duplicateIncident->marker_id;
                        $validated['duplicate_of_incident_id'] = $duplicateIncident->incident_id;
                        $markerId = $duplicateIncident->marker_id;
                    } else {
                        throw new HttpResponseException(response()->json([
                            'success' => false,
                            'message' => 'A fire report was already submitted for this area recently.',
                            'duplicate_incident_id' => $duplicateIncident->incident_id,
                        ], 422));
                    }
                } else {
                    $markerDescription = $validated['description'] ?? 'Citizen-submitted fire emergency.';
                    if (isset($validated['location_accuracy'], $validated['location_captured_at'])) {
                        $accuracyMeters = (int) round((float) $validated['location_accuracy']);
                        $capturedAt = \Illuminate\Support\Carbon::parse($validated['location_captured_at'])->toIso8601String();
                        $markerDescription .= " GPS accuracy ±{$accuracyMeters} m; captured {$capturedAt}.";
                    }

                    $createdMarker = Marker::create([
                        'title' => $validated['title'] ?? 'Citizen Fire Emergency',
                        'description' => $markerDescription,
                        'lat' => $lat,
                        'lng' => $lng,
                        'status' => 'inactive',
                        'category' => 'fire',
                    ]);

                    $validated['marker_id'] = $createdMarker->marker_id;
                }
            } else {
                $validated['marker_id'] = $markerId;
            }

            unset(
                $validated['lat'],
                $validated['lng'],
                $validated['title'],
                $validated['description'],
                $validated['location_accuracy'],
                $validated['location_captured_at']
            );

            $resolvedLat = $createdMarker?->lat ?? null;
            $resolvedLng = $createdMarker?->lng ?? null;

            if (($resolvedLat === null || $resolvedLng === null) && ! empty($validated['marker_id'])) {
                $existingMarker = Marker::query()
                    ->select(['marker_id', 'lat', 'lng'])
                    ->find($validated['marker_id']);

                $resolvedLat = $existingMarker?->lat;
                $resolvedLng = $existingMarker?->lng;
            }

            if ($this->supportsBarangayResolution()) {
                $validated['barangay_id'] = null;
                $validated['barangay_resolution_status'] = 'pending';
                $validated['barangay_resolution_source'] = null;
                $validated['barangay_resolved_at'] = null;

                if ($resolvedLat !== null && $resolvedLng !== null) {
                    $matchedBoundary = $boundaryMatcherService->locateBarangayBoundary(
                        (float) $resolvedLat,
                        (float) $resolvedLng
                    );

                    if ($matchedBoundary?->barangay_id) {
                        $validated['barangay_id'] = (int) $matchedBoundary->barangay_id;
                        $validated['barangay_resolution_status'] = 'matched';
                        $validated['barangay_resolution_source'] = $matchedBoundary->source ?: 'boundary-location';
                        $validated['barangay_resolved_at'] = now();
                    } else {
                        $validated['barangay_resolution_status'] = 'unmatched';
                        $validated['barangay_resolution_source'] = 'boundary-location';
                        $validated['barangay_resolved_at'] = now();
                    }
                }
            }

            if ($this->supportsIncidentStationColumn()) {
                if (empty($validated['station_id'])) {
                    $validated['station_id'] = $this->resolveJurisdictionStationIdOrDefault(
                        isset($validated['barangay_id']) ? (int) ($validated['barangay_id'] ?? 0) : null,
                        $resolvedLat !== null ? (float) $resolvedLat : null,
                        $resolvedLng !== null ? (float) $resolvedLng : null
                    );
                }
            } else {
                if (empty($validated['team_id'])) {
                    $fallbackStationId = ! empty($validated['station_id'])
                        ? (int) $validated['station_id']
                        : 1;

                    $fallbackTeam = $this->findAvailableTeamForStation($fallbackStationId)
                        ?? $this->findAnyTeamForStation($fallbackStationId)
                        ?? Team::query()->find(1);

                    if ($fallbackTeam?->team_id) {
                        $validated['team_id'] = (int) $fallbackTeam->team_id;
                    }
                }

                unset($validated['station_id']);
            }

            $validated['status'] = empty($validated['status'])
                ? self::STATUS_DB_REPORTED
                : $this->resolveDatabaseStatusName($validated['status']);
            if (Schema::hasColumn('incidents', 'dispatch_priority')) {
                $validated['dispatch_priority'] = $validated['dispatch_priority'] ?? 'medium';
            } else {
                unset($validated['dispatch_priority']);
            }

            $incident = Incident::create($validated);

            if ($createdMarker) {
                $createdMarker->update([
                    'status' => 'active',
                ]);
            }

            return $incident;
        });
        IncidentContextDispatcher::dispatchFor($data);
        IncidentFollowUpDispatcher::dispatchFor($data);

        $logUserId = (int) ($validated['reported_by'] ?? Auth::id() ?? 0);
        if ($logUserId > 0) {
            UserActivityLogger::log('created incident', $logUserId, $request);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fire report submitted. BFP processing has started.',
            'data' => $this->serializeIncident($data->fresh($this->getIncidentRelations())),
        ]);
    }

    private function resolveJurisdictionStationIdOrDefault(?int $barangayId, ?float $lat, ?float $lng): int
    {
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

            $barangayCityId = DB::table('barangays')
                ->where('barangay_id', $barangayId)
                ->value('city_id');

            $cityStationId = Station::query()
                ->leftJoin('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
                ->where('full_addresses.city_id', $barangayCityId)
                ->orderBy('stations.station_name')
                ->value('stations.station_id');

            if ($cityStationId) {
                return (int) $cityStationId;
            }
        }

        if ($lat !== null && $lng !== null) {
            $nearestStation = $this->getStationsSortedByDistance($lat, $lng)->first();

            if ($nearestStation?->station_id) {
                return (int) $nearestStation->station_id;
            }
        }

        return 1;
    }

    public function nearestAvailableResponder(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'incident_type_id' => 'nullable|integer',
        ]);

        $stations = $this->getStationsSortedByDistance(
            (float) $validated['lat'],
            (float) $validated['lng']
        );

        if ($stations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No station with valid map coordinates is available.',
                'data' => null,
            ], 404);
        }

        $stationLimit = ((int) ($validated['incident_type_id'] ?? 1)) === 1 ? 3 : 1;
        $candidateStations = $stations->take($stationLimit);

        foreach ($candidateStations as $station) {
            $team = $this->findAvailableTeamForStation((int) $station->station_id);

            if (! $team) {
                continue;
            }

            return response()->json([
                'success' => true,
                'message' => 'Nearest available responder found.',
                'data' => [
                    'station_id' => (int) $station->station_id,
                    'station_name' => $station->station_name,
                    'team_id' => (int) $team->team_id,
                    'distance_meters' => round((float) $station->distance_meters),
                    'availability' => 'available_team',
                ],
            ]);
        }

        $nearestStation = $candidateStations->first();

        return response()->json([
            'success' => true,
            'message' => 'No available team found in the nearest stations. Using nearest station as fallback.',
            'data' => [
                'station_id' => (int) $nearestStation->station_id,
                'station_name' => $nearestStation->station_name,
                'team_id' => null,
                'distance_meters' => round((float) $nearestStation->distance_meters),
                'availability' => 'nearest_station_only',
            ],
        ]);
    }

    public function show(Incident $incident)
    {
        $incident->load($this->getIncidentRelations());

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $this->serializeIncident($incident),
            ]);
        }

        return view('admin.incidents.show', compact('incident'));
    }

    public function edit(Incident $incident)
    {
        $markers = Marker::all();
        $incidentStatuses = $this->supportsLegacyIncidentStatus() ? IncidentStatus::all() : collect();
        $incidentTypes = $this->supportsLegacyIncidentType() ? IncidentType::all() : collect();
        $teams = Team::all();
        $reporters = User::all();
        
        return view('admin.incidents.edit', compact('incident', 'markers', 
            'incidentStatuses', 'incidentTypes', 'teams', 'reporters'));
    }

    public function update(Request $request, string $id)
    {
        $incident = Incident::findOrFail($id);

        $validated = $request->validate([
           'incident_id' => 'required|exists:incidents,incident_id',
            'team_id' => 'nullable|exists:teams,team_id',
        ]);

        abort_unless(strtolower(trim((string) $incident->status)) === 'reported', 409, 'Only reported incidents can be reassigned.');
        $team = ! empty($validated['team_id']) ? Team::findOrFail((int) $validated['team_id']) : null;
        $role = strtolower(trim((string) Auth::user()?->role?->role));
        if (! in_array($role, ['provincial admin', 'admin'], true)) {
            $stationId = StationOfficer::query()->where('user_id', Auth::id())->value('station_id');
            abort_unless($stationId && (int) $incident->station_id === (int) $stationId, 403);
        }
        if ($team) {
            abort_unless((int) $team->station_id === (int) $incident->station_id, 422, 'Use the command backup control for another station.');
            abort_unless(app(\App\Services\IncidentResourceDispatchService::class)->teamCanAcceptIncident($team, $incident->loadMissing('marker')), 422, 'The selected team is not dispatchable.');
        }
        if (Schema::hasColumn('incidents', 'dispatch_assigned_at')) {
            $validated['dispatch_assigned_at'] = $team ? now() : null;
        }

       $data =  $incident->update($validated);
       UserActivityLogger::log('updated incident team', Auth::user()?->user_id, $request);

      return response()->json(['success'=>true, 'thisdata'=>$data]);
    }

   public function updateCitizenReport(Request $request, string $incidentId)
   {
        $incident = Incident::with('marker')->findOrFail($incidentId);

        if ((int) $incident->reported_by !== (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to edit this report.'
            ], 403);
        }

        if ($this->resolveIncidentStatusLabel($incident) !== self::STATUS_NAME_REPORTED) {
            return response()->json([
                'success' => false,
                'message' => 'Only reported incidents can be edited.'
            ], 422);
        }

        UserActivityLogger::log('updated incident', Auth::id(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully.',
            'data' => $this->serializeIncident($incident->fresh($this->getIncidentRelations())),
        ]);
   }

   public function updateIncidentStatus(Request $request, string $incidentId, string $statusId)
{
    try {
        // Find the incident
        $incident = Incident::findOrFail($incidentId);
        
        $validStatuses = [
            self::STATUS_REPORTED,
            self::STATUS_RESPONDING,
            self::STATUS_ON_SCENE,
            self::STATUS_CONTROLLED,
            self::STATUS_CLOSED
        ];
        
        if (!in_array((int)$statusId, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status ID'
            ], 400);
        }
        
        // Always normalize to the DB enum value before persisting.
        $incident->status = $this->resolveDatabaseStatusName($this->mapStatusIdToName((int) $statusId));
        $incident->updated_at = now();
        $incident->save();
        
        $this->syncTeamAndStationOfficerStatuses($incident, (int) $statusId);
        UserActivityLogger::log('updated inc status', Auth::user()?->user_id, $request);
        
        $statusNames = [
            self::STATUS_REPORTED => 'Reported',
            self::STATUS_RESPONDING => 'Responding',
            self::STATUS_ON_SCENE => 'On Scene',
            self::STATUS_CONTROLLED => 'Controlled',
            self::STATUS_CLOSED => 'Closed'
        ];
        
        return response()->json([
            'success' => true,
            'message' => 'Incident status updated to ' . $statusNames[(int)$statusId]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error updating incident status: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update incident status: ' . $e->getMessage()
        ], 500);
    }
}

    public function markFalseAlarm($incidentId)
    {
        try {
            $incident = Incident::findOrFail($incidentId);
            $incident->status = self::STATUS_DB_CLOSED;
            $incident->updated_at = now();
            $incident->save();

            if ($incident->marker_id) {
                Marker::where('marker_id', $incident->marker_id)->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);
            }

            $this->syncTeamAndStationOfficerStatuses($incident, self::STATUS_CLOSED);
            UserActivityLogger::log('marked false alarm', Auth::user()?->user_id, request());

            return response()->json([
                'success' => true,
                'message' => 'Incident marked as false alarm and closed',
                'data' => $incident
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking incident as false alarm: ' . $e->getMessage(), [
                'incident_id' => $incidentId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update incident as false alarm'
            ], 500);
        }
    }
    public function getRecentIncidents(Request $request)
    {
        try {
            $since = $request->get('since');
            
            $query = Incident::with(['marker']);
            
            if ($since) {
                $query->where('created_at', '>', $since);
            }
            
            // Get only incidents from last 24 hours if no since param
            if (!$since) {
                $query->where('created_at', '>', now()->subDay());
            }
            
            $incidents = $query->orderBy('created_at', 'desc')->get()
                ->map(function (Incident $incident) {
                    return [
                        ...$incident->toArray(),
                        'incident_type' => $this->resolveIncidentTypeLabel($incident),
                        'incident_type_name' => $this->resolveIncidentTypeLabel($incident),
                        'incident_type_id' => null,
                        'incident_status' => $this->resolveIncidentStatusLabel($incident),
                        'incident_status_id' => $this->mapStatusNameToId($incident->status),
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $incidents,
                'count' => $incidents->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
        //validae 

    
    

    public function destroy(Incident $incident)
    {
        if ($incident->weather || $incident->spotInvestigationReport) {
            return back()->with('error', 'Cannot delete incident with existing related records.');
        }

        $incident->delete();

        return redirect()->route('incidents.index')
            ->with('success', 'Incident deleted successfully.');
    }
    
    public function latest()
    {
        $coord = Cache::remember('incident:latest:v2', now()->addSeconds(self::LATEST_INCIDENTS_CACHE_SECONDS), function () {
            $stationMarkers = Cache::remember('incident:latest:station-markers:v1', now()->addMinutes(5), function () {
                return Station::query()
                    ->select(['station_id', 'station_name', 'marker_id'])
                    ->with(['marker:marker_id,lat,lng'])
                    ->get()
                    ->filter(fn ($station) => $station->marker && $station->marker->lat !== null && $station->marker->lng !== null)
                    ->values();
            });

            return Incident::query()
                ->select([
                    'incident_id',
                    'marker_id',
                    'station_id',
                    'team_id',
                    'reported_by',
                    'barangay_id',
                    'barangay_resolution_status',
                    'barangay_resolution_source',
                    'barangay_resolved_at',
                    'status',
                    'created_at',
                    'updated_at',
                ])
                ->with([
                    'marker:marker_id,title,description,lat,lng,status,category,image,updated_at,created_at',
                    'station:station_id,station_name',
                    'barangay:barangay_id,barangay_name,city_id',
                ])
                ->latest('created_at')
                ->get()
                ->map(function (Incident $incident) use ($stationMarkers) {
                    $nearestStation = null;
                    $nearestStationDistanceMeters = null;

                    if ($incident->marker && $incident->marker->lat !== null && $incident->marker->lng !== null) {
                        foreach ($stationMarkers as $station) {
                            $distanceMeters = $this->calculateDistanceMeters(
                                (float) $incident->marker->lat,
                                (float) $incident->marker->lng,
                                (float) $station->marker->lat,
                                (float) $station->marker->lng
                            );

                            if ($nearestStationDistanceMeters === null || $distanceMeters < $nearestStationDistanceMeters) {
                                $nearestStationDistanceMeters = $distanceMeters;
                                $nearestStation = $station;
                            }
                        }
                    }

                    return [
                        'incident_id' => $incident->incident_id,
                        'marker_id' => $incident->marker_id,
                        'station_id' => $incident->station_id,
                        'station' => $incident->station ? [
                            'station_id' => $incident->station->station_id,
                            'station_name' => $incident->station->station_name,
                        ] : null,
                        'team_id' => $incident->team_id,
                        'reported_by' => $incident->reported_by,
                        'barangay_id' => $incident->barangay_id,
                        'barangay_name' => $incident->barangay?->barangay_name,
                        'barangay_resolution_status' => $incident->barangay_resolution_status ?? 'pending',
                        'barangay_resolution_source' => $incident->barangay_resolution_source,
                        'barangay_resolved_at' => optional($incident->barangay_resolved_at)->toISOString(),
                        'status' => $this->resolveIncidentStatusLabel($incident),
                        'incident_status' => $this->resolveIncidentStatusLabel($incident),
                        'incident_status_id' => $this->mapStatusNameToId($incident->status),
                        'incident_type_id' => null,
                        'incident_type' => $this->resolveIncidentTypeLabel($incident),
                        'incident_type_name' => $this->resolveIncidentTypeLabel($incident),
                        'marker' => $incident->marker ? [
                            'marker_id' => $incident->marker->marker_id,
                            'title' => $incident->marker->title,
                            'description' => $incident->marker->description,
                            'lat' => $incident->marker->lat,
                            'lng' => $incident->marker->lng,
                            'status' => $incident->marker->status,
                            'category' => $incident->marker->category,
                            'image' => $incident->marker->image,
                            'updated_at' => optional($incident->marker->updated_at)->toISOString(),
                            'created_at' => optional($incident->marker->created_at)->toISOString(),
                        ] : null,
                        'created_at' => optional($incident->created_at)->toISOString(),
                        'updated_at' => optional($incident->updated_at)->toISOString(),
                        'nearest_station_id' => $nearestStation?->station_id,
                        'nearest_station_name' => $nearestStation?->station_name,
                        'nearest_station_distance_meters' => $nearestStationDistanceMeters !== null
                            ? round($nearestStationDistanceMeters)
                            : null,
                    ];
                })
                ->values()
                ->all();
        });

        return response()->json([
            'success' => true,
            'data' => $coord,
        ]);
    }
    
    /**
     * Cancel an incident (for citizens)
     */
    public function cancelIncident(Request $request, $incidentId)
    {
        try {
            Log::info('Attempting to cancel incident', ['incident_id' => $incidentId, 'user_id' => auth()->id()]);
            
            $incident = Incident::find($incidentId);
            
            if (!$incident) {
                Log::warning('Incident not found', ['incident_id' => $incidentId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }
            
            // Check if user is the one who reported it
            if ($incident->reported_by != auth()->id()) {
                Log::warning('Unauthorized cancellation attempt', [
                    'incident_id' => $incidentId,
                    'user_id' => auth()->id(),
                    'reported_by' => $incident->reported_by
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You can only cancel your own reports'
                ], 403);
            }
            
            // Only allow cancellation if status is still reported.
            if ($this->resolveIncidentStatusLabel($incident) !== self::STATUS_NAME_REPORTED) {
                Log::warning('Cannot cancel incident with status', [
                    'incident_id' => $incidentId,
                    'status' => $incident->status
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Only reported incidents can be cancelled'
                ], 400);
            }
            
            // Update incident status to closed/cancelled
            $incident->status = self::STATUS_DB_CLOSED;
            $incident->save();
            
            Log::info('Incident cancelled successfully', [
                'incident_id' => $incidentId,
                'new_status' => self::STATUS_NAME_CLOSED
            ]);
            
            // Update the associated marker if needed
            if ($incident->marker_id) {
                $marker = Marker::find($incident->marker_id);
                if ($marker) {
                    // Cancelled incidents should hide the linked fire marker from active map views.
                    $marker->status = 'inactive';
                    $marker->save();
                    
                    Log::info('Marker status updated', [
                        'marker_id' => $marker->marker_id,
                        'new_status' => 'inactive'
                    ]);
                }
            }

            UserActivityLogger::log('cancelled incident', auth()->id(), $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Incident cancelled successfully',
                'data' => $incident
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error cancelling incident', [
                'incident_id' => $incidentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling incident: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update incident status (for admin/responding teams)
     */
    public function updateStatus(Request $request, $incidentId)
    {
        try {
            $validated = $request->validate([
                'incident_status_id' => 'nullable|integer|between:1,5',
                'status' => 'nullable|in:reported,en-route,on-scene,controlled,closed,completed,Reported,Responding,On Scene,Controlled,Closed'
            ]);
            
            $incident = Incident::find($incidentId);
            
            if (!$incident) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incident not found'
                ], 404);
            }
            
            $resolvedStatus = isset($validated['status'])
                ? $this->resolveDatabaseStatusName($validated['status'])
                : null;
            if (!$resolvedStatus && isset($validated['incident_status_id'])) {
                $resolvedStatus = $this->mapStatusIdToName((int) $validated['incident_status_id']);
            }
            if (!$resolvedStatus) {
                $resolvedStatus = $this->resolveDatabaseStatusName($incident->status);
            }

            $incident->status = $resolvedStatus;
            $incident->save();

            $this->syncTeamAndStationOfficerStatuses($incident, $this->mapStatusNameToId($resolvedStatus));
            UserActivityLogger::log('updated inc status', Auth::user()?->user_id, $request);
            
            Log::info('Incident status updated', [
                'incident_id' => $incidentId,
                'new_status' => $resolvedStatus
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Incident status updated successfully',
                'data' => $incident
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating incident status', [
                'incident_id' => $incidentId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error updating incident status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user's own incidents
     */
    public function myIncidents()
    {
        try {
            $incidents = Incident::with($this->getIncidentRelations())
                ->where('reported_by', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn (Incident $incident) => $this->serializeIncident($incident));
                
            return response()->json([
                'success' => true,
                'data' => $incidents
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching user incidents', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching incidents'
            ], 500);
        }
    }

    // In app/Http/Controllers/IncidentController.php

public function getRecentIncident(Request $request)
{
    try {
        $minutes = $request->input('minutes', 30);
        
        $recentIncidents = Incident::with($this->getIncidentRelations())
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($incident) {
                $lat = $incident->marker?->lat;
                $lng = $incident->marker?->lng;
                
                return [
                    'id' => $incident->incident_id,
                    'incident_id' => $incident->incident_id,
                    'alarmdatetime' => $incident->created_at,
                    'incident_type' => $this->resolveIncidentTypeLabel($incident),
                    'incident_type_name' => $this->resolveIncidentTypeLabel($incident),
                    'incident_type_id' => null,
                    'status' => $this->resolveIncidentStatusLabel($incident),
                    'incident_status' => $this->resolveIncidentStatusLabel($incident),
                    'incident_status_id' => $this->mapStatusNameToId($incident->status),
                    'lat' => $lat,
                    'lng' => $lng,
                    'address' => $incident->marker?->description,
                    'marker_id' => $incident->marker_id,
                    'marker' => $incident->marker,
                    'barangay_id' => $incident->barangay_id,
                    'barangay_name' => $incident->barangay?->barangay_name,
                    'barangay_resolution_status' => $incident->barangay_resolution_status ?? 'pending',
                    'created_at' => $incident->created_at,
                    'updated_at' => $incident->updated_at,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $recentIncidents,
            'count' => $recentIncidents->count(),
            'threshold_minutes' => $minutes
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error fetching recent incidents: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch recent incidents: ' . $e->getMessage()
        ], 500);
    }
}

public function autoAssignRecentUnassignedIncidents(Request $request)
{
    try {
        $minutes = max(1, (int) $request->input('minutes', 60));
        $result = app(IncidentAutoAssignmentService::class)->assignRecentUnassignedIncidents($minutes);

        if ($result['checked'] === 0) {
            return response()->json([
                'success' => true,
                'checked' => 0,
                'assigned' => 0,
                'window_minutes' => $minutes,
                'message' => 'No recent unassigned incidents to process.'
            ]);
        }

        return response()->json([
            'success' => true,
            'checked' => $result['checked'],
            'assigned' => count($result['assigned']),
            'window_minutes' => $minutes,
            'mode' => 'assign_all_recent_pending_incidents_per_cycle',
            'assigned_incident' => $result['assigned'][0] ?? null,
            'data' => $result['assigned'],
        ]);
    } catch (\Throwable $e) {
        Log::error('Error auto-assigning recent unassigned incidents: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to auto-assign pending incidents.'
        ], 500);
    }
}

public function autoAssignIncident(Request $request, string $incidentId)
{
    try {
        $incident = Incident::with('marker')->findOrFail($incidentId);
        $assigned = app(IncidentAutoAssignmentService::class)->assignSingleIncident($incident);

        return response()->json([
            'success' => true,
            'incident_id' => (int) $incident->incident_id,
            'assigned' => $assigned !== null,
            'assigned_incident' => $assigned,
            'message' => $assigned
                ? 'Incident auto-assigned successfully.'
                : 'No available team was assigned to this incident yet.',
        ]);
    } catch (\Throwable $e) {
        Log::error('Error auto-assigning incident by id: ' . $e->getMessage(), [
            'incident_id' => $incidentId,
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to auto-assign this incident.'
        ], 500);
    }
}
}
