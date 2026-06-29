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
use App\Support\IncidentContextDispatcher;
use App\Support\IncidentFollowUpDispatcher;
use App\Support\UserActivityLogger;
use App\Services\IncidentAutoAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->where('status', self::STATUS_DB_REPORTED)
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
        return ['marker', 'team', 'reporter', 'barangay.city'];
    }

    private function supportsBarangayResolution(): bool
    {
        return Schema::hasColumn('incidents', 'barangay_id')
            && Schema::hasColumn('incidents', 'barangay_resolution_status')
            && Schema::hasColumn('incidents', 'barangay_resolution_source')
            && Schema::hasColumn('incidents', 'barangay_resolved_at');
    }

    private function serializeIncident(Incident $incident): array
    {
        return [
            ...$incident->toArray(),
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

    private function getTeamMemberUserIds(?Team $team)
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

        $teamMemberUserIds = $this->getTeamMemberUserIds($team);
        if ($stationOfficerStatus && $teamMemberUserIds->isNotEmpty()) {
            StationOfficer::whereIn('user_id', $teamMemberUserIds->all())
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
        $stationOfficerMap = StationOfficer::whereIn('user_id', $leaderIds)
            ->pluck('station_id', 'user_id');

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
        $stationOfficerUserIds = StationOfficer::where('station_id', $stationId)
            ->pluck('user_id')
            ->filter()
            ->values();

        if ($stationOfficerUserIds->isEmpty()) {
            return null;
        }

        return Team::where('status', 'available')
            ->whereIn('team_leader', $stationOfficerUserIds->all())
            ->lockForUpdate()
            ->orderBy('team_id')
            ->first();
    }

    private function findAnyTeamForStation(int $stationId): ?Team
    {
        $stationOfficerUserIds = StationOfficer::where('station_id', $stationId)
            ->pluck('user_id')
            ->filter()
            ->values();

        if ($stationOfficerUserIds->isEmpty()) {
            return null;
        }

        return Team::whereIn('team_leader', $stationOfficerUserIds->all())
            ->orderBy('team_id')
            ->first();
    }

    private function getCandidateStationsForIncident(Incident $incident, float $lat, float $lng)
    {
        $stationLimit = strtolower((string) ($incident->marker?->category ?? '')) === 'fire' ? 5 : 1;

        return $this->getStationsSortedByDistance($lat, $lng)
            ->take($stationLimit);
    }

    private function markTeamResponding(Team $team): void
    {
        $team->status = 'responding';
        $team->updated_at = now();
        $team->save();

        $teamMemberUserIds = $this->getTeamMemberUserIds($team);
        if ($teamMemberUserIds->isNotEmpty()) {
            StationOfficer::whereIn('user_id', $teamMemberUserIds->all())
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'marker_id' => 'nullable|exists:markers,marker_id',
            'lat' => 'required_without:marker_id|numeric|between:-90,90',
            'lng' => 'required_without:marker_id|numeric|between:-180,180',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:reported,en-route,on-scene,controlled,closed,completed,Reported,Responding,On Scene,Controlled,Closed',
            'team_id' => 'nullable|exists:teams,team_id',
            'reported_by' => 'required|exists:users,user_id',
        ]);

        $markerId = $validated['marker_id'] ?? null;

        $data = DB::transaction(function () use ($validated, $markerId) {
            $createdMarker = null;

            if (! $markerId) {
                $lat = (float) $validated['lat'];
                $lng = (float) $validated['lng'];

                $duplicateIncident = $this->findRecentDuplicateIncident($lat, $lng);
                if ($duplicateIncident) {
                    throw new HttpResponseException(response()->json([
                        'success' => false,
                        'message' => 'A fire report was already submitted for this area recently.',
                        'duplicate_incident_id' => $duplicateIncident->incident_id,
                    ], 422));
                }

                $createdMarker = Marker::create([
                    'title' => $validated['title'] ?? 'Citizen Fire Emergency',
                    'description' => $validated['description'] ?? 'Citizen-submitted fire emergency.',
                    'lat' => $lat,
                    'lng' => $lng,
                    'status' => 'inactive',
                    'category' => 'fire',
                ]);

                $validated['marker_id'] = $createdMarker->marker_id;
            } else {
                $validated['marker_id'] = $markerId;
            }

            unset($validated['lat'], $validated['lng'], $validated['title'], $validated['description']);

            $validated['status'] = empty($validated['status'])
                ? self::STATUS_DB_REPORTED
                : $this->resolveDatabaseStatusName($validated['status']);

            if ($this->supportsBarangayResolution()) {
                $validated['barangay_resolution_status'] = 'pending';
                $validated['barangay_resolution_source'] = null;
                $validated['barangay_resolved_at'] = null;
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
        $stationMarkers = Station::with('marker')
            ->get()
            ->filter(fn ($station) => $station->marker && $station->marker->lat !== null && $station->marker->lng !== null)
            ->values();

        $coord = Incident::with($this->getIncidentRelations())
            ->latest()
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
                    'marker' => $incident->marker,
                    'created_at' => $incident->created_at,
                    'updated_at' => $incident->updated_at,
                    'nearest_station_id' => $nearestStation?->station_id,
                    'nearest_station_name' => $nearestStation?->station_name,
                    'nearest_station_distance_meters' => $nearestStationDistanceMeters !== null
                        ? round($nearestStationDistanceMeters)
                        : null,
                ];
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
            'mode' => 'single_oldest_fire_incident_per_cycle',
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
