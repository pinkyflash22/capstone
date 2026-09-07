<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\Incident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\OperationalMapScope;

class RouteController extends Controller
{
    public function __construct(private readonly OperationalMapScope $operationalScope)
    {
    }

    /**
     * Display a listing of all routes.
     */
    public function index()
    {
        try {
            $routes = $this->visibleRouteQuery()->orderBy('created_at', 'desc')->get();
            return response()->json($routes);
        } catch (\Exception $e) {
            Log::error('Error fetching routes: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch routes'], 500);
        }
    }

    /**
     * Store a newly created route in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'coordinates' => 'required|json',
                'total_distance' => 'required|numeric|min:0',
                'estimated_time' => 'required|numeric|min:0',
                'status' => 'required|in:active,moving,inactive',
                'route_source' => 'nullable|in:real,demo',
                'destination_title' => 'nullable|string|max:255',
                'destination_lat' => 'nullable|numeric|between:-90,90',
                'destination_lng' => 'nullable|numeric|between:-180,180',
                'origin_lat' => 'nullable|numeric|between:-90,90',
                'origin_lng' => 'nullable|numeric|between:-180,180',
                'incident_id'=>'required|exists:incidents,incident_id'
            ]);
            $this->validateRouteCoordinatesJson($validated['coordinates']);

            $supportsRouteSource = $this->routeColumnExists('route_source');

            if (($validated['route_source'] ?? 'real') === 'demo' && ! $supportsRouteSource) {
                Log::warning('Demo route persistence skipped because the routes table is missing route_source.');

                return response()->json([
                    'success' => false,
                    'persistence_unavailable' => true,
                    'message' => 'Demo route is available locally, but shared demo-route persistence is not configured yet.',
                ]);
            }

            $authenticatedTeam = $this->resolveAuthenticatedTeam();
            $requestedIncident = Incident::query()
                ->with('team:team_id,station_id')
                ->select(['incident_id', 'station_id', 'team_id', 'reported_by', 'barangay_id'])
                ->find($validated['incident_id']);

            abort_unless($requestedIncident && $this->canAccessIncident($requestedIncident), 403, 'You cannot create a route for this incident.');
            abort_unless($this->canMutateIncidentRoute($requestedIncident), 403, 'Only the assigned response team can start this route.');

            $requestedTeamId = $requestedIncident?->team_id ?? $authenticatedTeam?->team_id;

            if ($requestedTeamId && ($validated['route_source'] ?? 'real') !== 'demo') {
                $conflictingIncidentRoute = $this->findActiveConflictingIncidentRoute((int) $requestedTeamId, (int) $validated['incident_id']);
                if ($conflictingIncidentRoute) {
                    return response()->json([
                        'success' => false,
                        'error' => 'The response team is already committed to another active incident.',
                        'message' => 'Your team is already navigating to an ongoing incident. Finish or clear that incident route before starting another one.',
                        'active_route_id' => $conflictingIncidentRoute->route_id,
                        'active_incident_id' => $conflictingIncidentRoute->incident_id,
                    ], 409);
                }
            }

            // Add user info (API requests may not always have an authenticated user).
            $authUser = Auth::user();
            $validated['started_by'] = $authUser->name
                ?? $authUser->email
                ?? $request->input('started_by')
                ?? 'Unknown';
            $validated['route_source'] = $validated['route_source'] ?? 'real';
            $validated['current_position_index'] = 0;
            $validated['progress_percentage'] = 0;
            $validated['last_updated_at'] = now();
            $persistableRoute = $this->onlyExistingRouteColumns($validated);

            $writeResult = Cache::lock(
                'route-write:'.$validated['incident_id'].':'.$validated['route_source'],
                15
            )->block(5, function () use ($validated, $persistableRoute, $supportsRouteSource) {
                $existingRoute = Route::where('incident_id', $validated['incident_id'])
                    ->when($supportsRouteSource, fn ($query) => $query->where('route_source', $validated['route_source']))
                    ->whereIn('status', ['active', 'moving'])
                    ->latest('route_id')
                    ->first();

                if ($existingRoute) {
                    $existingRoute->update($this->onlyExistingRouteColumns([
                        'coordinates' => $validated['coordinates'],
                        'total_distance' => $validated['total_distance'],
                        'estimated_time' => $validated['estimated_time'],
                        'status' => 'active',
                        'route_source' => $validated['route_source'],
                        'destination_title' => $validated['destination_title'] ?? $existingRoute->destination_title,
                        'destination_lat' => $validated['destination_lat'] ?? $existingRoute->destination_lat,
                        'destination_lng' => $validated['destination_lng'] ?? $existingRoute->destination_lng,
                        'origin_lat' => $validated['origin_lat'] ?? $existingRoute->origin_lat,
                        'origin_lng' => $validated['origin_lng'] ?? $existingRoute->origin_lng,
                        'current_position_index' => 0,
                        'current_lat' => null,
                        'current_lng' => null,
                        'progress_percentage' => 0,
                        'started_by' => $validated['started_by'],
                        'started_at' => null,
                        'last_updated_at' => now(),
                    ]));

                    return ['route' => $existingRoute->fresh(), 'created' => false];
                }

                return ['route' => Route::create($persistableRoute), 'created' => true];
            });

            $this->flushActiveRouteCaches($validated['incident_id']);

            return response()->json([
                'success' => true,
                'message' => $writeResult['created'] ? 'Route saved successfully' : 'Route updated successfully',
                'data' => $writeResult['route'],
            ], $writeResult['created'] ? 201 : 200);
            
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json([
                'success' => false,
                'error' => 'This route is already being updated. Please retry.',
            ], 409);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error saving route: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to save route.'
            ], 500);
        }
    }

    /**
     * Get all active routes (status = active or moving)
     */
    public function getActive()
    {
        try {
            $activeRoutes = $this->visibleRouteQuery()
                    ->select($this->activeRouteSelectColumns())
                    ->with([
                        'incident:incident_id,station_id,team_id,incident_status_id',
                        'incident.team:team_id,commander_driver,pumper,team_leader,firefighter_officer_1,firefighter_officer_2,firefighter_officer_3,rescue_radio',
                    ])
                    ->whereIn('status', ['active', 'moving'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn ($route) => $this->transformRouteForFrontend($route))
                    ->values()
                    ->all();
            
            return response()->json($activeRoutes);
            
        } catch (\Exception $e) {
            return $this->activeRouteFailureResponse(
                'Failed to fetch active routes',
                $e,
                ['scope' => 'all']
            );
        }
    }

    /**
     * Get active routes for a specific incident.
     */
    public function getActiveByIncident($incidentId)
    {
        try {
            $activeRoutes = $this->visibleRouteQuery()
                    ->select($this->activeRouteSelectColumns())
                    ->with([
                        'incident:incident_id,station_id,team_id,incident_status_id',
                        'incident.team:team_id,commander_driver,pumper,team_leader,firefighter_officer_1,firefighter_officer_2,firefighter_officer_3,rescue_radio',
                    ])
                    ->where('incident_id', $incidentId)
                    ->whereIn('status', ['active', 'moving'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn ($route) => $this->transformRouteForFrontend($route))
                    ->values()
                    ->all();

            return response()->json($activeRoutes);
        } catch (\Exception $e) {
            return $this->activeRouteFailureResponse(
                'Failed to fetch active routes for incident',
                $e,
                ['scope' => 'incident', 'incident_id' => $incidentId]
            );
        }
    }

    /**
     * Update route status and position
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $route = $this->mutableRouteQuery()->findOrFail($id);
            
            $validated = $request->validate([
                'status' => 'required|in:active,moving,inactive',
                'current_position_index' => 'nullable|integer|min:0',
                'current_lat' => 'nullable|numeric|between:-90,90',
                'current_lng' => 'nullable|numeric|between:-180,180',
                'progress_percentage' => 'nullable|numeric|min:0|max:100'
            ]);
            
            // Update status
            $route->status = $validated['status'];
            
            // Update position if provided
            if (isset($validated['current_position_index'])) {
                $route->current_position_index = $validated['current_position_index'];
            }
            
            // Update coordinates if provided
            if (isset($validated['current_lat']) && isset($validated['current_lng'])) {
                $route->current_lat = $validated['current_lat'];
                $route->current_lng = $validated['current_lng'];
            }
            
            // Update progress if provided
            if (isset($validated['progress_percentage'])) {
                $route->progress_percentage = $validated['progress_percentage'];
            }
            
            // Set started_at when first moving
            if ($validated['status'] === 'moving' && !$route->started_at) {
                $route->started_at = now();
            }
            
            // Update last update timestamp
            $route->last_updated_at = now();
            
            // If completed, mark as inactive
            if ($route->progress_percentage >= 99.9) {
                $route->status = 'inactive';
            }
            
            $route->save();
            $this->flushActiveRouteCaches($route->incident_id);
            
            return response()->json([
                'success' => true,
                'message' => 'Route status updated',
                'data' => $route
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Route not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating route status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update route status.'
            ], 500);
        }
    }

    /**
     * Clear all active routes (set them to inactive)
     */
    public function clearActive()
    {
        try {
            $updated = $this->mutableRouteQuery()->whereIn('status', ['active', 'moving'])
                ->update(['status' => 'inactive']);
            $this->flushActiveRouteCaches();
            
            return response()->json([
                'success' => true,
                'message' => "Cleared {$updated} active routes",
                'count' => $updated
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error clearing active routes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear active routes'
            ], 500);
        }
    }

    /**
     * Get a specific route
     */
    public function show($id)
    {
        try {
            $route = $this->visibleRouteQuery()->findOrFail($id);
            
            // Format coordinates for frontend
            $route->formatted_coordinates = $route->getFormattedCoordinates();
            $route->leaflet_points = $route->getLeafletPoints();
            
            return response()->json($route);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Route not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching route: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch route'], 500);
        }
    }

    public function truckDetails($id)
    {
        try {
            $route = $this->visibleRouteQuery()->with([
                'incident.marker',
                'incident.team.commander',
                'incident.team.pumpers',
                'incident.team.teamLeader',
                'incident.team.officer1',
                'incident.team.officer2',
                'incident.team.officer3',
                'incident.team.rescueRadio',
            ])->findOrFail($id);

            $team = $route->incident?->team;
            $memberMap = [
                'Commander/Driver' => $team?->commander,
                'Pumper' => $team?->pumpers,
                'Team Leader' => $team?->teamLeader,
                'Firefighter Officer' => $team?->officer1,
                'Firefighter Officer' => $team?->officer2,
                'Firefighter Officer' => $team?->officer3,
                'Rescue Radio' => $team?->rescueRadio,
            ];

            $members = collect($memberMap)
                ->map(function ($user, $roleLabel) {
                    if (!$user) {
                        return null;
                    }

                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

                    return [
                        'user_id' => $user->user_id,
                        'name' => $fullName !== '' ? $fullName : ($user->name ?? ('User #' . $user->user_id)),
                        'role_label' => $roleLabel,
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'route_id' => $route->route_id,
                    'status' => $route->status,
                    'target_location' => [
                        'title' => $route->destination_title ?: ($route->incident?->marker?->title ?? 'Destination'),
                        'lat' => $route->destination_lat ?? $route->incident?->marker?->lat,
                        'lng' => $route->destination_lng ?? $route->incident?->marker?->lng,
                    ],
                    'team' => $team ? [
                        'team_id' => $team->team_id,
                        'status' => $team->status,
                        'label' => 'Team #' . $team->team_id,
                        'members' => $members,
                    ] : null,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Route not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching truck details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch truck details'
            ], 500);
        }
    }

    /**
     * Update the specified route.
     */
    public function update(Request $request, $id)
    {
        try {
            $route = $this->mutableRouteQuery()->findOrFail($id);
            
            $validated = $request->validate([
                'coordinates' => 'sometimes|json',
                'total_distance' => 'sometimes|numeric|min:0',
                'estimated_time' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:active,moving,inactive',
                'route_source' => 'nullable|in:real,demo',
                'destination_title' => 'nullable|string|max:255',
                'destination_lat' => 'nullable|numeric|between:-90,90',
                'destination_lng' => 'nullable|numeric|between:-180,180',
                'origin_lat' => 'nullable|numeric|between:-90,90',
                'origin_lng' => 'nullable|numeric|between:-180,180',
                'current_position_index' => 'nullable|integer|min:0',
                'current_lat' => 'nullable|numeric|between:-90,90',
                'current_lng' => 'nullable|numeric|between:-180,180',
                'progress_percentage' => 'nullable|numeric|min:0|max:100'
            ]);
            if (isset($validated['coordinates'])) {
                $this->validateRouteCoordinatesJson($validated['coordinates']);
            }
            
            $route->update($this->onlyExistingRouteColumns($validated));
            $route->last_updated_at = now();
            $route->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully',
                'data' => $route
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Route not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating route: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to update route.'
            ], 500);
        }
    }

    /**
     * Remove the specified route from storage.
     */
    public function destroy($id)
    {
        try {
            $route = $this->mutableRouteQuery()->findOrFail($id);
            $incidentId = $route->incident_id;
            $route->delete();
            $this->flushActiveRouteCaches($incidentId);
            
            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Route not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting route: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete route: ' . $e->getMessage()
            ], 500);
        }
    }

    private function transformRouteForFrontend(Route $route): array
    {
        return [
            'route_id' => $route->route_id,
            'incident_id' => $route->incident_id,
            'coordinates' => $route->coordinates,
            'formatted_coordinates' => $route->getFormattedCoordinates(),
            'leaflet_points' => $route->getLeafletPoints(),
            'remaining_points' => $route->getRemainingPoints(),
            'status' => $route->status,
            'route_source' => $route->getAttribute('route_source') ?: 'real',
            'incident_status_id' => $route->incident?->incident_status_id,
            'destination_title' => $route->destination_title,
            'destination_lat' => $route->destination_lat,
            'destination_lng' => $route->destination_lng,
            'origin_lat' => $route->getAttribute('origin_lat'),
            'origin_lng' => $route->getAttribute('origin_lng'),
            'current_position_index' => $route->current_position_index,
            'current_lat' => $route->current_lat,
            'current_lng' => $route->current_lng,
            'progress_percentage' => $route->progress_percentage,
            'route_team_id' => $route->incident?->team_id,
            'route_station_id' => $this->resolveRouteStationId($route->incident?->team, $route->incident?->station_id),
            'route_team_user_ids' => $this->resolveRouteTeamUserIds($route->incident?->team),
            'route_primary_user_id' => $this->resolvePrimaryRouteUserId($route->incident?->team),
            'created_at' => optional($route->created_at)->toISOString(),
            'updated_at' => optional($route->updated_at)->toISOString(),
            'last_updated_at' => optional($route->last_updated_at)->toISOString(),
        ];
    }

    private function resolveRouteStationId(?Team $team, ?int $incidentStationId = null): ?int
    {
        if ($team?->station_id) {
            return (int) $team->station_id;
        }

        if ($incidentStationId) {
            return (int) $incidentStationId;
        }

        if (!$team) {
            return null;
        }

        $teamUserIds = $this->getTeamStationOfficerIds($team);

        if (empty($teamUserIds)) {
            return null;
        }

        return Cache::remember('route:team-station:' . $team->team_id, now()->addSeconds(60), function () use ($teamUserIds) {
            return StationOfficer::query()
                ->whereIn('user_id', $teamUserIds)
                ->value('station_id');
        });
    }

    private function resolveRouteTeamUserIds(?Team $team): array
    {
        if (!$team) {
            return [];
        }

        return Cache::remember('route:team-user-ids:' . $team->team_id, now()->addSeconds(60), function () use ($team) {
            return StationOfficer::query()
                ->whereIn('station_officer_id', $this->getTeamStationOfficerIds($team))
                ->orderBy('station_officer_id')
                ->pluck('user_id')
                ->filter()
                ->map(fn ($userId) => (int) $userId)
                ->values()
                ->all();
        });
    }

    private function resolvePrimaryRouteUserId(?Team $team): ?int
    {
        if (!$team || !$team->commander_driver) {
            return null;
        }

        return Cache::remember('route:team-primary-user:' . $team->team_id, now()->addSeconds(60), function () use ($team) {
            return StationOfficer::query()
                ->where('station_officer_id', $team->commander_driver)
                ->value('user_id');
        });
    }

    private function getTeamStationOfficerIds(Team $team): array
    {
        return array_values(array_filter([
            $team->commander_driver,
            $team->pumper,
            $team->team_leader,
            $team->firefighter_officer_1,
            $team->firefighter_officer_2,
            $team->firefighter_officer_3,
            $team->rescue_radio,
        ]));
    }

    private function flushActiveRouteCaches(?int $incidentId = null): void
    {
        Cache::forget('map:routes:active:v2');

        if ($incidentId !== null) {
            Cache::forget('map:routes:active:incident:' . $incidentId . ':v2');
        }
    }

    private function resolveAuthenticatedTeam(): ?Team
    {
        $userId = Auth::user()?->user_id;
        if (!$userId) {
            return null;
        }

        $stationOfficerId = Cache::remember('route:auth-user-station-officer:' . $userId, now()->addSeconds(60), function () use ($userId) {
            return StationOfficer::query()
                ->where('user_id', $userId)
                ->value('station_officer_id');
        });

        if (!$stationOfficerId) {
            return null;
        }

        return Cache::remember('route:auth-user-team:' . $userId, now()->addSeconds(30), function () use ($stationOfficerId) {
            return Team::query()
                ->where('commander_driver', $stationOfficerId)
                ->orWhere('pumper', $stationOfficerId)
                ->orWhere('team_leader', $stationOfficerId)
                ->orWhere('firefighter_officer_1', $stationOfficerId)
                ->orWhere('firefighter_officer_2', $stationOfficerId)
                ->orWhere('firefighter_officer_3', $stationOfficerId)
                ->orWhere('rescue_radio', $stationOfficerId)
                ->first();
        });
    }

    private function findActiveConflictingIncidentRoute(int $teamId, int $requestedIncidentId): ?Route
    {
        return Route::query()
            ->select(['route_id', 'incident_id', 'status'])
            ->whereIn('status', ['active', 'moving'])
            ->when($this->routeColumnExists('route_source'), fn ($query) => $query->where('route_source', '!=', 'demo'))
            ->where('incident_id', '!=', $requestedIncidentId)
            ->whereHas('incident', function ($query) use ($teamId) {
                $query->where('team_id', $teamId);
            })
            ->orderByDesc('route_id')
            ->first();
    }

    private function activeRouteSelectColumns(): array
    {
        return array_values(array_filter([
            'route_id',
            'incident_id',
            'coordinates',
            'status',
            $this->routeColumnExists('route_source') ? 'route_source' : null,
            'destination_title',
            'destination_lat',
            'destination_lng',
            $this->routeColumnExists('origin_lat') ? 'origin_lat' : null,
            $this->routeColumnExists('origin_lng') ? 'origin_lng' : null,
            'current_position_index',
            'current_lat',
            'current_lng',
            'progress_percentage',
            'created_at',
            'updated_at',
            'last_updated_at',
        ]));
    }

    private function onlyExistingRouteColumns(array $attributes): array
    {
        return collect($attributes)
            ->filter(fn ($value, $column) => $this->routeColumnExists((string) $column))
            ->all();
    }

    private function routeColumnExists(string $column): bool
    {
        static $columns = [];

        return $columns[$column] ??= Schema::hasColumn('routes', $column);
    }

    private function validateRouteCoordinatesJson(string $coordinates): void
    {
        if (strlen($coordinates) > 2_000_000) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'coordinates' => 'Route geometry is too large.',
            ]);
        }

        $decoded = json_decode($coordinates, true);
        $pointCount = 0;
        $invalid = false;
        $walk = function ($value) use (&$walk, &$pointCount, &$invalid): void {
            if (! is_array($value) || $invalid) {
                return;
            }

            if (count($value) >= 2 && is_numeric($value[0] ?? null) && is_numeric($value[1] ?? null)) {
                $lng = (float) $value[0];
                $lat = (float) $value[1];
                $pointCount++;
                if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90 || $pointCount > 20_000) {
                    $invalid = true;
                }
                return;
            }

            foreach ($value as $child) {
                $walk($child);
            }
        };
        $walk($decoded);

        if ($invalid || $pointCount < 2) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'coordinates' => 'Route geometry must contain 2 to 20,000 valid longitude/latitude points.',
            ]);
        }
    }

    private function mutableRouteQuery(): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return Route::query()->whereRaw('1 = 0');
        }

        if ($this->operationalScope->role($user) !== 'firefighter') {
            return $this->visibleRouteQuery();
        }

        $team = $this->resolveAuthenticatedTeam();

        return $team
            ? Route::query()->whereHas('incident', fn ($query) => $query->where('team_id', $team->team_id))
            : Route::query()->whereRaw('1 = 0');
    }

    private function canMutateIncidentRoute(Incident $incident): bool
    {
        $user = Auth::user();
        if (! $user || ! $this->canAccessIncident($incident)) {
            return false;
        }

        if ($this->operationalScope->role($user) !== 'firefighter') {
            return true;
        }

        $team = $this->resolveAuthenticatedTeam();

        return $team && (int) $incident->team_id === (int) $team->team_id;
    }

    private function activeRouteFailureResponse(string $message, \Throwable $exception, array $context = [])
    {
        $errorId = (string) Str::uuid();

        Log::error($message, array_merge($context, [
            'error_id' => $errorId,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'exception' => $exception,
        ]));

        $response = [
            'error' => $message,
            'error_id' => $errorId,
        ];

        if (app()->environment(['local', 'staging'])) {
            $response['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        return response()->json($response, 500);
    }

    private function visibleRouteQuery(): Builder
    {
        $query = Route::query();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $role = $this->operationalScope->role($user);
        if ($this->operationalScope->hasProvinceWideAccess($user)) {
            return $query;
        }

        if ($this->operationalScope->isStationResponder($user)) {
            $stationIds = $this->operationalScope->stationIds($user);

            if ($stationIds->isEmpty()) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('incident', function ($incidentQuery) use ($stationIds) {
                $incidentQuery->whereIn('station_id', $stationIds->all())
                    ->orWhereHas('team', fn ($teamQuery) => $teamQuery->whereIn('station_id', $stationIds->all()));
            });
        }

        if (in_array($role, ['barangay', 'barangay captain', 'barangay officer', 'barangay_officer'], true)) {
            $barangayIds = $this->operationalScope->barangayIds($user);

            return $barangayIds->isNotEmpty()
                ? $query->whereHas('incident', fn ($incidentQuery) => $incidentQuery->whereIn('barangay_id', $barangayIds->all()))
                : $query->whereRaw('1 = 0');
        }

        return $query->whereHas('incident', function ($incidentQuery) use ($user) {
            $incidentQuery->where('reported_by', $user->user_id);

            if (Schema::hasColumn('incidents', 'duplicate_of_incident_id')) {
                $incidentQuery->orWhereHas('duplicateReports', fn ($duplicates) => $duplicates->where('reported_by', $user->user_id));
            }
        });
    }

    private function canAccessIncident(Incident $incident): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $role = $this->operationalScope->role($user);
        if ($this->operationalScope->hasProvinceWideAccess($user)) {
            return true;
        }

        if ($this->operationalScope->isStationResponder($user)) {
            $stationIds = $this->operationalScope->stationIds($user);
            $incidentStationId = $incident->station_id ?: $incident->team?->station_id;

            return $incidentStationId && $stationIds->contains((int) $incidentStationId);
        }

        if (in_array($role, ['barangay', 'barangay captain', 'barangay officer', 'barangay_officer'], true)) {
            $barangayIds = $this->operationalScope->barangayIds($user);

            return $incident->barangay_id && $barangayIds->contains((int) $incident->barangay_id);
        }

        return (int) $incident->reported_by === (int) $user->user_id;
    }

}
