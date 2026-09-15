<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Marker;

use App\Models\Station;
use App\Models\Equipment;

use App\Models\EquipmentToStation;
use App\Models\StationOfficer;
use App\Models\Team;

use App\Models\User;
use App\Support\IncidentContextDispatcher;
use App\Support\IncidentOccurrenceSynchronizer;
use App\Models\Role;
use App\Models\Province;
use App\Models\SpotInvestigationReport;
use App\Models\ProgressInvestigationReport;
use App\Models\FinalInvestigationReport;
use App\Models\HistoricalFireIncident;
use App\Models\Barangay;
use App\Models\FullAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use App\Support\UserActivityLogger;
use App\Services\IncidentResponderSnapshotService;
use App\Services\StationBarangayCoverageService;
use App\Services\InvestigationReportImageService;
use Carbon\Carbon;

class ManagementController extends Controller
{
      private $roleCodes = [
        'citizen' => 'CIT',
        'admin' => 'ADM',
        'firefighter' => 'BFP',
        'chief' => 'FMC',
        'station admin' => 'STM',
        'dispatcher' => 'DSP',
        'paramedic' => 'PAR',
        'investigator' => 'INV',
    ];

    protected function currentUserId(): ?int
    {
        return Auth::user()?->user_id ?? Auth::id();
    }

    protected function currentStationOfficer(): ?StationOfficer
    {
        $userId = $this->currentUserId();

        if (! $userId) {
            return null;
        }

        return StationOfficer::where('user_id', $userId)->first();
    }

    protected function currentRole(): string
    {
        return strtolower(trim((string) (Auth::user()?->role?->role ?? '')));
    }

    protected function isProvincialAdmin(): bool
    {
        return $this->currentRole() === 'provincial admin';
    }

    public function updateInvestigationReport(Request $request, string $kind, int $id, InvestigationReportImageService $imageService)
    {
        $this->resolveManagedInvestigationReport($kind, $id);

        return match ($kind) {
            'spot' => app(\App\Http\Controllers\SpotInvestigationReportController::class)->update($request, (string) $id, $imageService),
            'progress' => app(\App\Http\Controllers\ProgressInvestigationReportController::class)->update($request, (string) $id, $imageService),
            'final' => app(\App\Http\Controllers\FinalInvestigationReportController::class)->update($request, (string) $id, $imageService),
            default => abort(404),
        };
    }

    public function destroyInvestigationReport(Request $request, string $kind, int $id)
    {
        $report = $this->resolveManagedInvestigationReport($kind, $id);

        return match ($kind) {
            'spot' => app(\App\Http\Controllers\SpotInvestigationReportController::class)->destroy((string) $id),
            'progress' => app(\App\Http\Controllers\ProgressInvestigationReportController::class)->destroy($report),
            'final' => app(\App\Http\Controllers\FinalInvestigationReportController::class)->destroy($report),
            default => abort(404),
        };
    }

    public function reviewInvestigationReport(Request $request, string $kind, int $id)
    {
        $report = $this->resolveManagedInvestigationReport($kind, $id);

        abort_unless(Schema::hasColumn($report->getTable(), 'review_status'), 503, 'Report review workflow is not available yet.');

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->forceFill([
            'review_status' => $validated['decision'],
            'review_comments' => $validated['review_comments'] ?? null,
            'reviewed_by_user_id' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        $this->clearManagementReportCaches($report);
        UserActivityLogger::log($validated['decision'] . ' ' . $kind . ' investigation report', Auth::id(), $request);

        return response()->json([
            'success' => true,
            'data' => [
                'report_id' => $report->getKey(),
                'report_kind' => $kind,
                'review_status' => $report->review_status,
                'review_comments' => $report->review_comments,
                'reviewed_at' => optional($report->reviewed_at)->toIso8601String(),
                'reviewed_by_name' => trim((Auth::user()?->first_name ?? '') . ' ' . (Auth::user()?->last_name ?? '')),
            ],
        ]);
    }

    private function resolveManagedInvestigationReport(string $kind, int $id)
    {
        $model = match ($kind) {
            'spot' => SpotInvestigationReport::query(),
            'progress' => ProgressInvestigationReport::query(),
            'final' => FinalInvestigationReport::query(),
            default => abort(404),
        };
        $report = $model->findOrFail($id);
        if ($this->isProvincialAdmin()) {
            return $report;
        }

        $stationId = (int) ($this->currentStationOfficer()?->station_id ?? 0);
        abort_unless($stationId > 0, 403);
        $belongsToStation = StationOfficer::query()
            ->whereKey($report->station_officer_id)
            ->where('station_id', $stationId)
            ->exists();
        abort_unless($belongsToStation, 404);

        return $report;
    }

    private function clearManagementReportCaches($report): void
    {
        Cache::forget('management:reports:all');

        $stationId = StationOfficer::query()
            ->whereKey($report->station_officer_id)
            ->value('station_id');

        if ($stationId) {
            Cache::forget('management:reports:' . $stationId);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if ($this->isProvincialAdmin()) {
            $mapmarker = Marker::with('stations')->get();
            $stations = Station::with(['marker', 'profileMedia', 'galleryMedia', 'address.barangay.city.province'])->get();
            $stationOfficers = StationOfficer::with(['user.role', 'station'])->get();
            $stationOfficerIds = $stationOfficers->pluck('station_officer_id')->filter();
            $stationOfficerUserIds = $stationOfficers->pluck('user_id')->filter();
            $stationTeamIds = Team::query()->pluck('team_id')->all();

            $incidents = Incident::with(['marker', 'reporter', 'team', 'historicalRecords.barangay'])->get();
            $alerts = Incident::with('marker')->get();
            $provinces = Province::orderBy('province_name')->get();

            $chiefRole = Role::where('role', 'chief')->first();
            $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();

            $personnel = $stationOfficers->map(function ($officer) {
                return [
                    'user_id' => $officer->user?->user_id,
                    'station_officer_id' => $officer->station_officer_id,
                    'first_name' => $officer->user?->first_name,
                    'last_name' => $officer->user?->last_name,
                    'email' => $officer->user?->email,
                    'account_code' => $officer->user?->account_code,
                    'contact_number' => $officer->user?->contact_number,
                    'status' => $officer->status,
                    'rank' => $officer->rank,
                    'station_id' => $officer->station_id,
                    'role' => $officer->user?->role ? [
                        'role_id' => $officer->user->role->role_id,
                        'role' => $officer->user->role->role,
                    ] : null,
                ];
            })->values();

            $equipment = EquipmentToStation::with(['equipment', 'station'])
                ->get()
                ->map(function ($assignment) {
                    return [
                        'equipment_station_id' => $assignment->equipment_station_id,
                        'equipment_id' => $assignment->equipment_id,
                        'station_id' => $assignment->station_id,
                        'equipment_name' => $assignment->equipment?->equipment_name,
                        'type' => $assignment->equipment?->type,
                        'serial_number' => $assignment->equipment?->serial_number,
                        'model' => $assignment->equipment?->model,
                        'category' => $assignment->equipment?->category,
                        'available_quantity' => (int) ($assignment->available_quantity ?? 0),
                        'damaged_quantity' => (int) ($assignment->damaged_quantity ?? 0),
                        'maintenance_quantity' => (int) ($assignment->maintenance_quantity ?? 0),
                        'status' => $this->deriveEquipmentAvailabilityStatus($assignment),
                    ];
                })->values();

            $reports = $this->buildStationReports(null, $stationOfficerIds->all(), $stationTeamIds, $stationOfficerUserIds->all());
            $roles = Role::where('role', '!=', 'anonymous')->orderBy('role')->get();
            $managedBarangays = Barangay::with('city')->orderBy('barangay_name')->get();
            $totalIncidents = $incidents;

            return view('chief.management', compact(
                'totalIncidents',
                'mapmarker',
                'incidents',
                'reports',
                'alerts',
                'stations',
                'provinces',
                'chiefs',
                'personnel',
                'equipment',
                'roles',
                'managedBarangays'
            ));
        }

        $thisUser = $this->currentUserId();
        $thisStation = $this->currentStationOfficer();
        $stationExact = $thisStation?->station_id;

    
        $mapmarker = Marker::with('stations')->get();

        $stations = Station::where('station_id', $stationExact)->with(['marker', 'profileMedia', 'galleryMedia', 'address.barangay.city.province'])->get();
        $stationOfficers = StationOfficer::with(['user.role', 'station'])
            ->where('station_id', $stationExact)
            ->get();
        $stationOfficerIds = $stationOfficers->pluck('station_officer_id')->filter();
        $stationOfficerUserIds = $stationOfficers->pluck('user_id')->filter();
        $stationTeamIds = $this->stationTeamIds((int) $stationExact, $stationOfficerIds->all());

        $incidents = $this->buildStationIncidentQuery((int) $stationExact, $stationTeamIds, $stationOfficerUserIds->all())
            ->with(['marker', 'reporter', 'team', 'historicalRecords.barangay'])
            ->get();

        $alerts = Incident::with('marker')
          
            ->whereIn('team_id', $stationTeamIds)
            ->get();

        $provinces = Province::orderBy('province_name')->get();

        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();

        $personnel = $stationOfficers->map(function ($officer) {
            return [
                'user_id' => $officer->user?->user_id,
                'station_officer_id' => $officer->station_officer_id,
                'first_name' => $officer->user?->first_name,
                'last_name' => $officer->user?->last_name,
                'email' => $officer->user?->email,
                'account_code' => $officer->user?->account_code,
                'contact_number' => $officer->user?->contact_number,
                'status' => $officer->status,
                'rank' => $officer->rank,
                'station_id' => $officer->station_id,
                'role' => $officer->user?->role ? [
                    'role_id' => $officer->user->role->role_id,
                    'role' => $officer->user->role->role,
                ] : null,
            ];
        })->values();

        $equipment = EquipmentToStation::with(['equipment', 'station'])
            ->where('station_id', $stationExact)
            ->get()
            ->map(function ($assignment) {
                return [
                    'equipment_station_id' => $assignment->equipment_station_id,
                    'equipment_id' => $assignment->equipment_id,
                    'station_id' => $assignment->station_id,
                    'equipment_name' => $assignment->equipment?->equipment_name,
                    'type' => $assignment->equipment?->type,
                    'serial_number' => $assignment->equipment?->serial_number,
                    'model' => $assignment->equipment?->model,
                    'category' => $assignment->equipment?->category,
                    'available_quantity' => (int) ($assignment->available_quantity ?? 0),
                    'damaged_quantity' => (int) ($assignment->damaged_quantity ?? 0),
                    'maintenance_quantity' => (int) ($assignment->maintenance_quantity ?? 0),
                    'status' => $this->deriveEquipmentAvailabilityStatus($assignment),
                ];
            })->values();

        $reports = $this->buildStationReports((int) $stationExact, $stationOfficerIds->all(), $stationTeamIds, $stationOfficerUserIds->all());
  
        $roles = Role::where('role', '!=', 'anonymous')
            ->orderBy('role')
            ->get();
        $managedBarangays = $this->getManagedBarangays($stationExact);

        $totalIncidents = $incidents;

        return view('chief.management', compact(
            'totalIncidents',
            'mapmarker',
            'incidents',
            'reports',
            'alerts',
            'stations',
            'provinces',
            'chiefs',
            'personnel',
            'equipment',
            'roles',
            'managedBarangays'
        ));
    }

    public function updateStation(Request $request, Station $station)
    {
        $stationOfficer = $this->currentStationOfficer();

        if (! $this->isProvincialAdmin() && (!$stationOfficer || (int) $stationOfficer->station_id !== (int) $station->station_id)) {
            abort(403, 'You are not allowed to update this station.');
        }

        if (! in_array($this->currentRole(), ['chief', 'provincial admin'], true)) {
            abort(403, 'Only chiefs can update station information.');
        }

        $validated = $request->validate([
            'station_name' => 'required|string|max:255',
            'station_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('stations', 'station_code')->ignore($station->station_id, 'station_id'),
            ],
            'contact_number' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('stations', 'email')->ignore($station->station_id, 'station_id'),
            ],
            'province_id' => ['required', 'integer', 'exists:provinces,province_id'],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'city_id')->where(fn ($query) => $query->where('province_id', $request->integer('province_id'))),
            ],
            'barangay_id' => [
                'required',
                'integer',
                Rule::exists('barangays', 'barangay_id')->where(fn ($query) => $query->where('city_id', $request->integer('city_id'))),
            ],
            'street' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($station, $validated) {
            $address = FullAddress::firstOrCreate([
                'street' => trim($validated['street']),
                'barangay_id' => (int) $validated['barangay_id'],
            ]);

            $station->update([
                'station_name' => $validated['station_name'],
                'station_code' => $validated['station_code'] ?: $station->station_code,
                'contact_number' => $validated['contact_number'] ?: null,
                'email' => $validated['email'] ?: null,
                'address_id' => $address->address_id,
            ]);
        });

        try {
            app(StationBarangayCoverageService::class)->syncStation((int) $station->station_id);
        } catch (\Throwable $exception) {
            // The station update is already valid. The municipality fallback in
            // getManagedBarangays keeps station-scoped forms usable if coverage
            // synchronization is temporarily unavailable.
            report($exception);
        }

        $this->forgetStationManagementCaches((int) $station->station_id);
        UserActivityLogger::log('updated station information', auth()->user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Station information updated successfully.',
            'data' => $station->fresh(['marker', 'address.barangay.city.province']),
        ]);
    }

    /**
     * Cache invalidation is best-effort after the station transaction commits.
     * A temporary cache outage must not turn a successful database update into
     * a misleading HTTP 500 response.
     */
    protected function forgetStationManagementCaches(int $stationId): void
    {
        $keys = [
            "resource-management:station:{$stationId}:v4:stations",
            "resource-management:station:{$stationId}:v4:boundaries",
        ];

        foreach ($keys as $key) {
            try {
                Cache::forget($key);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Get incidents data for API with filters
     */
//    public function getIncidents(Request $request)
// {
//     $accountId = Auth::user()->user_id;
    
//     // FIXED: $thisperson is not defined - you need to get the station officer record first
//     $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
    
//     if (!$stationOfficer) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Station officer not found'
//         ], 404);
//     }
    
//     $stationId = $stationOfficer->station_id;
    
//     // FIXED: Use proper relationship names based on your schema
//     $stationperson = StationOfficer::where('station_id', $stationId)
//         ->with(['station', 'station.users'])  // Make sure these relationships exist
//         ->get();
//       // Array to store team_ids where the logged-in user is the team leader
//     $userTeamIds = [];
//        // Loop through station officers and check if user is team_leader
//     foreach ($stationperson as $officer) {
//         // Check if the team_leader column matches the current user's ID
//         if (isset($officer->team_leader) && $officer->team_leader == $accountId) {
//             // If match found, add the team_id to the array
//             if (isset($officer->team_id)) {
//                 $userTeamIds[] = $officer->team_id;
//             }
//         }
//     }
//     // $teams = Team::where('team_leader');
//     // FIXED: 'team_id' might not exist - use correct column name
//     // Check your incidents table structure first
//     $query = Incident::query(); // Start with base query
    
//     // Only add where condition if 'team_id' column exists
//     if (Schema::hasColumn('incidents', 'team_id')) {
//         $query->whereNotNull('team_id');
//     }
    
//     $query->with(['marker', 'incidentType', 'incidentStatus', 'reporter']);
    
//     // Apply search filter
//     if ($request->has('search') && $request->search) {
//         $search = $request->search;
//         $query->where(function($q) use ($search) {
//             $q->where('incident_id', 'LIKE', "%{$search}%")
//               ->orWhere('reported_by', 'LIKE', "%{$search}%")
//               ->orWhereHas('marker', function($sub) use ($search) {
//                   $sub->where('title', 'LIKE', "%{$search}%")
//                       ->orWhere('description', 'LIKE', "%{$search}%");
//               });
//         });
//     }
    
//     // Apply status filter
//     if ($request->has('status') && $request->status) {
//         $query->where('incident_status_id', $request->status);
//     }
    
//     // Apply date filter
//     if ($request->has('date_filter') && $request->date_filter) {
//         switch($request->date_filter) {
//             case 'today':
//                 $query->whereDate('created_at', Carbon::today());
//                 break;
//             case 'week':
//                 $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
//                 break;
//             case 'month':
//                 $query->whereMonth('created_at', Carbon::now()->month);
//                 break;
//         }
//     }
    
//     // Apply sorting
//     $sortBy = $request->get('sort_by', 'created_at');
//     $sortOrder = $request->get('sort_order', 'desc');
    
//     // Validate sort column to prevent SQL injection
//     $allowedSortColumns = ['created_at', 'incident_id', 'incident_status_id'];
//     if (!in_array($sortBy, $allowedSortColumns)) {
//         $sortBy = 'created_at';
//     }
    
//     $query->orderBy($sortBy, $sortOrder);
    
//     $incidents = $query->get();
    
//     return response()->json([
//         'success' => true,
//         'data' => $incidents,
//         'station_officers' => $stationperson  // Include if needed
//     ]);
// }
public function getIncidents(Request $request, IncidentResponderSnapshotService $snapshotService)
{
    if ($this->isProvincialAdmin()) {
        $query = Incident::query()
            ->with(['marker', 'reporter', 'team', 'historicalRecords.barangay', 'responderSnapshot']);

        if ($request->has('search') && $request->search) {
            $search = mb_substr(trim((string) $request->search), 0, 100);
            $query->where(function($q) use ($search) {
                $q->whereRaw('CAST(incident_id AS TEXT) ILIKE ?', ["%{$search}%"])
                  ->orWhereHas('reporter', function ($reporterQuery) use ($search) {
                      $reporterQuery->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('historicalRecords', function ($historicalQuery) use ($search) {
                      $historicalQuery->where('source_name', 'LIKE', "%{$search}%")
                          ->orWhere('incident_location', 'LIKE', "%{$search}%")
                          ->orWhere('fire_cause', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('marker', function($sub) use ($search) {
                      $sub->where('title', 'LIKE', "%{$search}%")
                          ->orWhere('description', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status) {
            $statusMap = [
                '1' => ['Reported', 'reported'],
                '2' => ['Responding', 'responding'],
                '3' => ['Active', 'active', 'Controlled', 'controlled', 'On Scene', 'on scene'],
                '4' => ['Resolved', 'resolved', 'Closed', 'closed'],
            ];
            $statusAliases = $statusMap[(string) $request->status] ?? [$request->status];
            $query->where(function ($statusQuery) use ($request, $statusAliases) {
                $statusQuery->where('incident_status_id', $request->status)
                    ->orWhereIn('status', $statusAliases);
            });
        }

        if ($request->has('date_filter') && $request->date_filter) {
            switch($request->date_filter) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereYear('created_at', Carbon::now()->year)
                        ->whereMonth('created_at', Carbon::now()->month);
                    break;
            }
        }

        $sortBy = in_array($request->get('sort_by'), ['created_at', 'incident_id', 'incident_status_id'], true)
            ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $incidents = $query->paginate($this->perPage($request));
        $snapshotService->attachResponderDetails($incidents->getCollection());

        return response()->json([
            'success' => true,
            'data' => $incidents->items(),
            'pagination' => $this->paginationMeta($incidents),
            'user_team_ids' => Team::query()->pluck('team_id')->all(),
        ]);
    }

    $stationOfficer = $this->currentStationOfficer();

    if (! $stationOfficer?->station_id) {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'No station assignment found for this account.',
        ]);
    }

    $stationOfficers = StationOfficer::where('station_id', $stationOfficer->station_id)
        ->get(['station_officer_id', 'user_id']);
    $stationOfficerIds = $stationOfficers->pluck('station_officer_id')->filter()->all();
    $stationUserIds = $stationOfficers->pluck('user_id')->filter()->all();
    $stationTeamIds = $this->stationTeamIds((int) $stationOfficer->station_id, $stationOfficerIds);

    $query = $this->buildStationIncidentQuery((int) $stationOfficer->station_id, $stationTeamIds, $stationUserIds)
        ->with(['marker', 'reporter', 'team', 'historicalRecords.barangay', 'responderSnapshot']);
    
    // Apply search filter
    if ($request->has('search') && $request->search) {
        $search = mb_substr(trim((string) $request->search), 0, 100);
        $query->where(function($q) use ($search) {
            $q->whereRaw('CAST(incident_id AS TEXT) ILIKE ?', ["%{$search}%"])
              ->orWhereHas('reporter', function ($reporterQuery) use ($search) {
                  $reporterQuery->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('historicalRecords', function ($historicalQuery) use ($search) {
                  $historicalQuery->where('source_name', 'LIKE', "%{$search}%")
                      ->orWhere('incident_location', 'LIKE', "%{$search}%")
                      ->orWhere('fire_cause', 'LIKE', "%{$search}%");
              })
              ->orWhereHas('marker', function($sub) use ($search) {
                  $sub->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
              });
        });
    }
    
    // Apply status filter
    if ($request->has('status') && $request->status) {
        $statusMap = [
            '1' => ['Reported', 'reported'],
            '2' => ['Responding', 'responding'],
            '3' => ['Active', 'active', 'Controlled', 'controlled', 'On Scene', 'on scene'],
            '4' => ['Resolved', 'resolved', 'Closed', 'closed'],
        ];
        $statusAliases = $statusMap[(string) $request->status] ?? [$request->status];

        $query->where(function ($statusQuery) use ($request, $statusAliases) {
            $statusQuery->where('incident_status_id', $request->status)
                ->orWhereIn('status', $statusAliases);
        });
    }
    
    // Apply date filter
    if ($request->has('date_filter') && $request->date_filter) {
        switch($request->date_filter) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereYear('created_at', Carbon::now()->year)
                    ->whereMonth('created_at', Carbon::now()->month);
                break;
        }
    }
    
    // Apply sorting
    $sortBy = in_array($request->get('sort_by'), ['created_at', 'incident_id', 'incident_status_id'], true)
        ? $request->get('sort_by') : 'created_at';
    $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
    $query->orderBy($sortBy, $sortOrder);
    $incidents = $query->paginate($this->perPage($request));
    $snapshotService->attachResponderDetails($incidents->getCollection());
    
    return response()->json([
        'success' => true,
        'data' => $incidents->items(),
        'pagination' => $this->paginationMeta($incidents),
        'user_team_ids' => $stationTeamIds
    ]);
}
// public function getIncidents(Request $request)
// {
//     $accountId = Auth::user()->user_id;
    
//     // Get the station officer record for the logged-in user
//     $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
    
//     if (!$stationOfficer) {
//         return response()->json([
//             'success' => false,
//             'message' => 'Station officer not found'
//         ], 404);
//     }
    
//     $stationId = $stationOfficer->station_id;
    
//     // Get all station officers for this station with their relationships
//     $stationperson = StationOfficer::where('station_id', $stationId)
//         ->with(['station'])
//         ->get();
    
//     // Array to store team_ids where the logged-in user is the team_leader
//     $userTeamIds = [];
    
//     // Loop through station officers and find their teams
//     foreach ($stationperson as $officer) {
//         // Check if this officer has a team_id (if the column exists in station_officers)
//         // Note: Based on your schema, station_officers doesn't have team_id column
//         // So we need to query teams where any of the team members match this officer's user_id
        
//         // Query teams where this officer is the team_leader
//         $team = Team::whereIn('team_leader', $officer->user_id)->first();
        
//         if ($team) {
//             // Check if the team_leader matches the current logged-in user's ID
//             if ($team->team_leader == $accountId) {
//                 $userTeamIds[] = $team->team_id;
//             }
//         }
//     }
    
//     // Alternative: Direct query to get all teams where logged-in user is team_leader
//     // and the team has at least one member from this station
//     $directTeamIds = Team::where('team_leader', $accountId)
//         ->pluck('team_id')
//         ->toArray();
    
//     // Merge both approaches and remove duplicates
//     $userTeamIds = array_unique(array_merge($userTeamIds, $directTeamIds));
    
//     // If no teams found, return empty result
//     if (empty($userTeamIds)) {
//         return response()->json([
//             'success' => true,
//             'data' => [],
//             'message' => 'User is not a team leader for any team'
//         ]);
//     }
    
//     // Query incidents filtered by these team_ids
//     $query = Incident::where('team_id', $userTeamIds)->with(['marker', 'incidentType', 'incidentStatus', 'reporter']);
    
//     // Filter incidents by the teams the user leads
//     $query->whereIn('team_id', $userTeamIds);
    
//     // Apply search filter
//     if ($request->has('search') && $request->search) {
//         $search = $request->search;
//         $query->where(function($q) use ($search) {
//             $q->where('incident_id', 'LIKE', "%{$search}%")
//               ->orWhere('reported_by', 'LIKE', "%{$search}%")
//               ->orWhereHas('marker', function($sub) use ($search) {
//                   $sub->where('title', 'LIKE', "%{$search}%")
//                       ->orWhere('description', 'LIKE', "%{$search}%");
//               });
//         });
//     }
    
//     // Apply status filter
//     if ($request->has('status') && $request->status) {
//         $query->where('incident_status_id', $request->status);
//     }
    
//     // Apply date filter
//     if ($request->has('date_filter') && $request->date_filter) {
//         switch($request->date_filter) {
//             case 'today':
//                 $query->whereDate('created_at', Carbon::today());
//                 break;
//             case 'week':
//                 $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
//                 break;
//             case 'month':
//                 $query->whereMonth('created_at', Carbon::now()->month);
//                 break;
//         }
//     }
    
//     // Apply sorting
//     $sortBy = $request->get('sort_by', 'created_at');
//     $sortOrder = $request->get('sort_order', 'desc');
//     $query->orderBy($sortBy, $sortOrder);
    
//     $incidents = $query->paginate(15);
    
//     return response()->json([
//         'success' => true,
//         'data' => $incidents,
//         'user_team_ids' => $userTeamIds,
//         'station_officers' => $stationperson  // Optional: for debugging
//     ]);
// }
    /**
     * Get personnel data for API
     */
    // Add these methods to your ManagementController.php

/**
 * Store a newly created personnel
 */
public function storePersonnel(Request $request)
{
    try {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
             'middle_name' => 'nullable|string|max:255', 
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            // 'password' => 'required|string|min:8',
            'account_code' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,role_id',
        ]);
        
        $role = Role::find($validated['role_id']);
         if (!$role) {
                throw new \Exception('Role not found.');
            }

        if (strtolower($role->role) === 'anonymous') {
            throw new \Exception('Anonymous role cannot be assigned from this page.');
        }

        $accountCode = $this->generateAccountCode($role->role);
        $addressId = null;


        $personnel = new User();
        $personnel->first_name = $validated['first_name'];
        $personnel->middle_name = $validated['middle_name'];

        $personnel->last_name = $validated['last_name'];
        $personnel->email = $validated['email'];
        // $personnel->password = bcrypt($validated['password']);
        // $personnel->account_code = $validated['account_code'] ?? null;
        $personnel->account_code = $accountCode ?? null;

        $personnel->contact_number = $validated['contact_number'] ?? null;
        $personnel->role_id = $validated['role_id'];
        $personnel->is_verified = true;
         $personnel->address_id = $addressId;
        $personnel->save();
        UserActivityLogger::log('created personnel', Auth::user()?->user_id, $request);
        
        return response()->json([
            'success' => true,
            'message' => 'Personnel created successfully',
            'data' => $personnel->load('role')
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create personnel: ' . $e->getMessage()
        ], 500);
    }
}

   private function generateAccountCode($roleName)
    {
        // Get the role code prefix
        $roleCode = $this->roleCodes[strtolower($roleName)] ?? 'USR';
        
        // Get the last account code for this specific role
        $lastUser = User::where('account_code', 'LIKE', $roleCode . '-%')
            ->orderBy('user_id', 'desc')
            ->first();
        
        if ($lastUser) {
            // Extract the number from the last code
            $lastNumber = (int) substr($lastUser->account_code, strlen($roleCode) + 1);
            $newNumber = $lastNumber + 1;
        } else {
            // Start from 1 if no existing users with this role
            $newNumber = 1;
        }
        
        // Format with leading zeros (4 digits)
        return $roleCode . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

/**
 * Store a newly created equipment
 */


public function storeEquipment(Request $request)
{
    try {
        $validated = $request->validate([
            'equipment_name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100|unique:equipment,serial_number',
            'model' => 'nullable|string|max:100',
            'status' => 'required|in:available,in-use,maintenance,damaged',
            'equipment_category_id' => 'nullable|exists:equipment_categories,equipment_category_id'
        ]);
        
        $equipment = Equipment::create($validated);
        UserActivityLogger::log('created equipment', Auth::user()?->user_id, $request);
        
        return response()->json([
            'success' => true,
            'message' => 'Equipment created successfully',
            'data' => $equipment->load(['category', 'stationEquipments'])
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create equipment: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Store a newly created incident
 */
public function storeIncident(Request $request)
{
    try {
        if ($request->input('entry_mode') === 'historical_fire_record') {
            return $this->storeHistoricalFireIncident($request);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:reported,responding,active,resolved',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'reported_by' => 'nullable|string|max:255'
        ]);
        
        // Create marker first
        $marker = Marker::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'marker_category_id' => 1 // Default incident category
        ]);
        
        // Map priority and status to IDs
        $priorityMap = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $statusMap = ['reported' => 1, 'responding' => 2, 'active' => 3, 'resolved' => 4];
        
        // Create incident
        $incident = Incident::create([
            'marker_id' => $marker->marker_id,
            'incident_type_id' => $priorityMap[$validated['priority']],
            'incident_status_id' => $statusMap[$validated['status']],
            'reported_by' => $validated['reported_by'] ?? null,
            'reported_at' => now(),
            'created_at' => now()
        ]);
        IncidentContextDispatcher::dispatchFor($incident);
        UserActivityLogger::log('created incident', Auth::user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Incident created successfully',
            'data' => $incident->load(['marker', 'incidentType', 'incidentStatus'])
        ], 201);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create incident: ' . $e->getMessage()
        ], 500);
    }
}

protected function buildStationIncidentQuery(int $stationId, array $stationTeamIds, array $stationUserIds)
{
    $barangayIds = $this->getManagedBarangays($stationId)->pluck('barangay_id')->all();

    return Incident::query()->where(function ($query) use ($stationId, $stationTeamIds, $stationUserIds, $barangayIds) {
        $query->where('station_id', $stationId)
            ->orWhere(function ($legacy) use ($stationTeamIds, $stationUserIds, $barangayIds) {
                $legacy->whereNull('station_id')->where(function ($fallback) use ($stationTeamIds, $stationUserIds, $barangayIds) {
                    $hasFallback = false;
                    foreach ([['team_id', $stationTeamIds], ['reported_by', $stationUserIds], ['barangay_id', $barangayIds]] as [$column, $ids]) {
                        if (! empty($ids)) {
                            $hasFallback ? $fallback->orWhereIn($column, $ids) : $fallback->whereIn($column, $ids);
                            $hasFallback = true;
                        }
                    }
                    if (! $hasFallback) $fallback->whereRaw('1 = 0');
                });
            });
    });
}

protected function stationTeamIds(int $stationId, array $stationOfficerIds): array
{
    return Team::query()
        ->where(function ($query) use ($stationId, $stationOfficerIds) {
            $query->where('station_id', $stationId);

            foreach (['team_leader', 'commander_driver', 'pumper', 'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio'] as $column) {
                if (! empty($stationOfficerIds)) {
                    $query->orWhereIn($column, $stationOfficerIds);
                }
            }
        })
        ->pluck('team_id')
        ->map(fn ($id) => (int) $id)
        ->all();
}

protected function getManagedBarangays(?int $stationId)
{
    if (! $stationId) {
        return collect();
    }

    $coveredBarangays = Barangay::query()
        ->with('city.province')
        ->whereHas('stationCoverage', function ($query) use ($stationId) {
            $query->where('station_id', $stationId)
                ->where(function ($coverageQuery) {
                    $coverageQuery->whereNull('is_active')
                        ->orWhere('is_active', true);
                });
        })
        ->orderBy('barangay_name')
        ->get(['barangay_id', 'barangay_name', 'city_id']);

    if ($coveredBarangays->isNotEmpty()) {
        return $coveredBarangays;
    }

    // Older stations can have a complete address without generated coverage
    // rows. In that case, use only the barangays from the assigned station's
    // municipality instead of disabling historical incident entry.
    $stationCityId = Station::query()
        ->whereKey($stationId)
        ->whereNotNull('stations.address_id')
        ->join('full_addresses', 'stations.address_id', '=', 'full_addresses.address_id')
        ->join('barangays as station_barangay', 'full_addresses.barangay_id', '=', 'station_barangay.barangay_id')
        ->value('station_barangay.city_id');

    if (! $stationCityId) {
        return collect();
    }

    return Barangay::query()
        ->with('city.province')
        ->where('city_id', (int) $stationCityId)
        ->orderBy('barangay_name')
        ->get(['barangay_id', 'barangay_name', 'city_id']);
}

protected function storeHistoricalFireIncident(Request $request)
{
    $stationOfficer = $this->currentStationOfficer();

    if (! $stationOfficer?->station_id) {
        return response()->json([
            'success' => false,
            'message' => 'No station assignment found for this account.'
        ], 422);
    }

    $managedBarangayIds = $this->getManagedBarangays($stationOfficer->station_id)
        ->pluck('barangay_id')
        ->map(fn ($id) => (int) $id)
        ->all();

    if (empty($managedBarangayIds)) {
        return response()->json([
            'success' => false,
            'message' => 'No barangays could be resolved from the assigned station address. Update the station municipality and barangay first.',
        ], 422);
    }

    $validated = $request->validate([
        'entry_mode' => 'required|in:historical_fire_record',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'incident_date' => 'required|date|before_or_equal:today',
        'incident_time' => 'nullable|date_format:H:i',
        'barangay_id' => [
            'required',
            'integer',
            Rule::in($managedBarangayIds),
        ],
        'incident_location' => 'required|string|max:255',
        'fire_classification' => 'required|in:accidental,intentional,undetermined',
        'fire_cause' => 'nullable|string|max:255',
        'incident_count' => 'nullable|integer|min:1|max:9999',
        'injured_count' => 'nullable|integer|min:0|max:9999',
        'death_count' => 'nullable|integer|min:0|max:9999',
        'estimated_damage' => 'nullable|numeric|min:0',
        'lat' => 'required|numeric|between:-90,90',
        'lng' => 'required|numeric|between:-180,180',
        'source_name' => 'nullable|string|max:255',
        'source_reference' => 'nullable|string|max:255',
        'weather_condition' => 'nullable|string|max:255',
        'temperature' => 'nullable|numeric',
        'humidity' => 'nullable|numeric|min:0|max:100',
        'wind_speed' => 'nullable|numeric|min:0',
        'wind_direction' => 'nullable|string|max:100',
        'notes' => 'nullable|string',
    ]);

    $historicalIncident = DB::transaction(function () use ($validated, $stationOfficer, $request) {
        $marker = Marker::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $validated['incident_location'],
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'status' => 'inactive',
            'category' => 'fire',
        ]);

        $incidentTimestamp = trim(($validated['incident_date'] ?? '') . ' ' . ($validated['incident_time'] ?? '00:00'));

        $incident = Incident::create([
            'marker_id' => $marker->marker_id,
            'status' => 'Resolved',
            'team_id' => null,
            'reported_by' => $stationOfficer->user_id,
            'barangay_id' => (int) $validated['barangay_id'],
        ]);
        IncidentOccurrenceSynchronizer::verify(
            $incident,
            Carbon::parse($incidentTimestamp),
            'historical_bfp_record',
            Auth::user()?->user_id
        );
        $incident->incident_status_id = 4;
        $incident->save();

        IncidentContextDispatcher::dispatchFor($incident);

        $historical = HistoricalFireIncident::create([
            'barangay_id' => (int) $validated['barangay_id'],
            'incident_id' => $incident->incident_id,
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'] ? $validated['incident_time'] . ':00' : null,
            'source_name' => $validated['source_name'] ?: 'Station Historical Fire Entry',
            'source_type' => 'bfp_record',
            'source_reference' => $validated['source_reference'] ?? null,
            'fire_classification' => $validated['fire_classification'],
            'fire_cause' => $validated['fire_cause'] ?? null,
            'incident_location' => $validated['incident_location'],
            'incident_count' => (int) ($validated['incident_count'] ?? 1),
            'injured_count' => (int) ($validated['injured_count'] ?? 0),
            'death_count' => (int) ($validated['death_count'] ?? 0),
            'estimated_damage' => $validated['estimated_damage'] ?? null,
            'temperature' => $validated['temperature'] ?? null,
            'humidity' => $validated['humidity'] ?? null,
            'wind_speed' => $validated['wind_speed'] ?? null,
            'wind_direction' => $validated['wind_direction'] ?? null,
            'weather_condition' => $validated['weather_condition'] ?? null,
            'is_verified' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        UserActivityLogger::log('created historical fire incident', Auth::user()?->user_id, $request);

        return $incident->load(['marker', 'reporter', 'historicalRecords.barangay']);
    });

    return response()->json([
        'success' => true,
        'message' => 'Historical fire record saved successfully.',
        'data' => $historicalIncident,
    ], 201);
}
    public function getPersonnel(Request $request)
    {
        $stationOfficer = $this->currentStationOfficer();

        if (! $this->isProvincialAdmin() && ! $stationOfficer?->station_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No station assignment found for this account.',
            ]);
        }

        $query = StationOfficer::with(['user.role'])
            ->when(! $this->isProvincialAdmin(), fn ($builder) => $builder->where('station_id', $stationOfficer?->station_id));
        
        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%")
                        ->orWhere('account_code', 'LIKE', "%{$search}%");
                })->orWhere('rank', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply status filter (active/inactive based on status_id)
        if ($request->filled('status')) {
            if (in_array($request->status, ['active', 'inactive'], true)) {
                $statusId = $request->status === 'active' ? 1 : 0;
                $query->whereHas('user', function ($userQuery) use ($statusId) {
                    $userQuery->where('status_id', $statusId);
                });
            } else {
                $query->where('status', mb_substr(trim((string) $request->status), 0, 30));
            }
        }

        $paginator = $query->orderBy('station_officer_id')->paginate($this->perPage($request));
        $personnel = collect($paginator->items())->map(function ($officer) {
            $resolvedStatus = (int) ($officer->user?->status_id ?? 0) === 1 ? 'active' : 'inactive';

            return [
                'user_id' => $officer->user?->user_id,
                'station_officer_id' => $officer->station_officer_id,
                'first_name' => $officer->user?->first_name,
                'last_name' => $officer->user?->last_name,
                'email' => $officer->user?->email,
                'account_code' => $officer->user?->account_code,
                'contact_number' => $officer->user?->contact_number,
                'status_id' => $officer->user?->status_id,
                'status' => strtolower((string) ($officer->status ?: $resolvedStatus)),
                'rank' => $officer->rank,
                'role' => $officer->user?->role ? [
                    'role_id' => $officer->user->role->role_id,
                    'role' => $officer->user->role->role,
                ] : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $personnel,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    /**
     * Get equipment data for API
     */
    public function getEquipment(Request $request)
    {
        $stationOfficer = $this->currentStationOfficer();

        if (! $this->isProvincialAdmin() && ! $stationOfficer?->station_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No station assignment found for this account.',
            ]);
        }

        $query = EquipmentToStation::with(['equipment.category', 'station'])
            ->when(! $this->isProvincialAdmin(), fn ($builder) => $builder->where('station_id', $stationOfficer?->station_id));
        
        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('equipment', function ($equipmentQuery) use ($search) {
                    $equipmentQuery->where('equipment_name', 'LIKE', "%{$search}%")
                        ->orWhere('model', 'LIKE', "%{$search}%")
                        ->orWhere('serial_number', 'LIKE', "%{$search}%")
                        ->orWhere('type', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($request->has('status') && $request->status) {
            $status = $request->status;
            $query->where(function ($q) use ($status) {
                if ($status === 'available') {
                    $q->where('available_quantity', '>', 0);
                } elseif ($status === 'maintenance') {
                    $q->where('maintenance_quantity', '>', 0);
                } elseif ($status === 'damaged') {
                    $q->where('damaged_quantity', '>', 0);
                } elseif ($status === 'in-use') {
                    $q->where('available_quantity', '<=', 0)
                      ->where('maintenance_quantity', '<=', 0)
                      ->where('damaged_quantity', '<=', 0);
                }
            });
        }

        $paginator = $query->orderBy('equipment_station_id')->paginate($this->perPage($request));
        $equipment = collect($paginator->items())->map(function ($assignment) {
            return [
                'equipment_station_id' => $assignment->equipment_station_id,
                'equipment_id' => $assignment->equipment_id,
                'station_id' => $assignment->station_id,
                'station_name' => $assignment->station?->station_name,
                'equipment_name' => $assignment->equipment?->equipment_name,
                'type' => $assignment->equipment?->type,
                'serial_number' => $assignment->equipment?->serial_number,
                'model' => $assignment->equipment?->model,
                'equipment_category_id' => $assignment->equipment?->equipment_category_id,
                'category_name' => $assignment->equipment?->category?->category_name,
                'available_quantity' => (int) ($assignment->available_quantity ?? 0),
                'damaged_quantity' => (int) ($assignment->damaged_quantity ?? 0),
                'maintenance_quantity' => (int) ($assignment->maintenance_quantity ?? 0),
                'status' => $this->deriveEquipmentAvailabilityStatus($assignment),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $equipment,
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    public function getReports(Request $request)
    {
        $stationOfficer = $this->currentStationOfficer();

        if (! $this->isProvincialAdmin() && ! $stationOfficer?->station_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No station assignment found for this account.',
            ]);
        }

        $stationId = $this->isProvincialAdmin() ? null : (int) $stationOfficer->station_id;
        $officers = StationOfficer::query()
            ->when($stationId, fn ($query) => $query->where('station_id', $stationId))
            ->get(['station_officer_id', 'user_id']);
        $stationOfficerIds = $officers->pluck('station_officer_id')->filter()->all();
        $stationUserIds = $officers->pluck('user_id')->filter()->all();
        $stationTeamIds = $stationId ? $this->stationTeamIds($stationId, $stationOfficerIds) : Team::pluck('team_id')->all();

        $cacheKey = 'management:reports:' . ($stationId ?: 'all');
        $reports = Cache::remember($cacheKey, now()->addSeconds(30), fn () =>
            $this->buildStationReports($stationId, $stationOfficerIds, $stationTeamIds, $stationUserIds)
        );

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $reports = $reports->filter(function ($report) use ($search) {
                return str_contains(strtolower(($report['title'] ?? '') . ' ' . ($report['incident_title'] ?? '') . ' ' . ($report['investigator_name'] ?? '')), $search);
            });
        }

        if ($request->filled('status')) {
            $reports = $reports->where('report_kind', $request->status);
        }

        $reports = $reports->values();
        $paginator = new LengthAwarePaginator(
            $reports->forPage(max(1, $request->integer('page', 1)), $this->perPage($request))->values(),
            $reports->count(),
            $this->perPage($request),
            max(1, $request->integer('page', 1))
        );

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => $this->paginationMeta($paginator),
        ]);
    }

    private function buildStationReports(?int $stationId, array $stationOfficerIds, array $stationTeamIds = [], array $stationUserIds = [])
    {
        $barangayIds = $stationId ? $this->getManagedBarangays($stationId)->pluck('barangay_id')->all() : [];
        $scopeIncident = function ($query) use ($stationId, $stationTeamIds, $stationUserIds, $barangayIds) {
            $query->where('station_id', $stationId)
                ->orWhere(function ($legacy) use ($stationTeamIds, $stationUserIds, $barangayIds) {
                    $legacy->whereNull('station_id')->where(function ($fallback) use ($stationTeamIds, $stationUserIds, $barangayIds) {
                        $hasFallback = false;
                        foreach ([['team_id', $stationTeamIds], ['reported_by', $stationUserIds], ['barangay_id', $barangayIds]] as [$column, $ids]) {
                            if ($ids) {
                                $hasFallback ? $fallback->orWhereIn($column, $ids) : $fallback->whereIn($column, $ids);
                                $hasFallback = true;
                            }
                        }
                        if (! $hasFallback) $fallback->whereRaw('1 = 0');
                    });
                });
        };

        $spotReports = SpotInvestigationReport::with(['incident.marker', 'incident.reporter', 'investigator.user', 'progressInvestigation.finalInvestigation', 'reviewer'])
            ->when($stationId, fn ($query) => $query->where(function ($scope) use ($stationOfficerIds, $scopeIncident) {
                $scope->whereIn('station_officer_id', $stationOfficerIds)
                    ->orWhereHas('incident', $scopeIncident);
            }))
            ->get()
            ->map(function ($report) {
                return [
                    'id' => 'spot-' . $report->spot_investigation_report_id,
                    'report_id' => $report->spot_investigation_report_id,
                    'report_kind' => 'spot',
                    'title' => 'Spot Investigation Report',
                    'type' => 'Spot Investigation',
                    'incident_id' => $report->incident_id,
                    'incident_title' => $report->incident?->marker?->title ?? ('Incident #' . $report->incident_id),
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'author' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'status' => $report->progressInvestigation ? 'progressed' : 'filed',
                    'created_at' => optional($report->created_at)?->toIso8601String(),
                    'description' => $report->incident?->marker?->description,
                    ...$this->investigationReviewPayload($report),
                    'fields' => collect($report->getAttributes())->except(['created_at', 'updated_at'])->all(),
                ];
            });

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'investigator.user', 'fireOrigin', 'finalInvestigation', 'reviewer'])
            ->when($stationId, fn ($query) => $query->where(function ($scope) use ($stationOfficerIds, $scopeIncident) {
                $scope->whereIn('station_officer_id', $stationOfficerIds)
                    ->orWhereHas('spotInvestigation.incident', $scopeIncident);
            }))
            ->get()
            ->map(function ($report) {
                return [
                    'id' => 'progress-' . $report->progress_investigation_report_id,
                    'report_id' => $report->progress_investigation_report_id,
                    'report_kind' => 'progress',
                    'title' => $report->report_stage_label,
                    'type' => $report->report_stage_label,
                    'incident_id' => $report->spotInvestigation?->incident_id,
                    'incident_title' => $report->spotInvestigation?->incident?->marker?->title ?? ('Incident #' . ($report->spotInvestigation?->incident_id ?? '')),
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'author' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'status' => $report->finalInvestigation ? 'finalized' : 'conducting',
                    'created_at' => optional($report->created_at)?->toIso8601String(),
                    'description' => $report->fire_cause,
                    ...$this->investigationReviewPayload($report),
                    'fields' => collect($report->getAttributes())->except(['created_at', 'updated_at'])->all(),
                ];
            });

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.investigator.user', 'reviewer'])
            ->when($stationId, fn ($query) => $query->where(function ($scope) use ($stationOfficerIds, $scopeIncident) {
                $scope->whereIn('station_officer_id', $stationOfficerIds)
                    ->orWhereHas('progressInvestigation.spotInvestigation.incident', $scopeIncident);
            }))
            ->get()
            ->map(function ($report) {
                $investigator = $report->progressInvestigation?->investigator?->user;
                return [
                    'id' => 'final-' . $report->final_investigation_report_id,
                    'report_id' => $report->final_investigation_report_id,
                    'report_kind' => 'final',
                    'title' => 'Final Investigation Report',
                    'type' => 'Final Investigation',
                    'incident_id' => $report->progressInvestigation?->spotInvestigation?->incident_id,
                    'incident_title' => $report->progressInvestigation?->spotInvestigation?->incident?->marker?->title ?? ('Incident #' . ($report->progressInvestigation?->spotInvestigation?->incident_id ?? '')),
                    'investigator_name' => trim(($investigator?->first_name ?? '') . ' ' . ($investigator?->last_name ?? '')),
                    'author' => trim(($investigator?->first_name ?? '') . ' ' . ($investigator?->last_name ?? '')),
                    'status' => 'completed',
                    'created_at' => optional($report->created_at)?->toIso8601String(),
                    'description' => $report->exact_address,
                    ...$this->investigationReviewPayload($report),
                    'fields' => collect($report->getAttributes())->except(['created_at', 'updated_at'])->all(),
                ];
            });

        return $spotReports
            ->concat($progressReports)
            ->concat($finalReports)
            ->sortByDesc('created_at')
            ->values();
    }

    private function investigationReviewPayload($report): array
    {
        $status = $report->review_status ?: 'submitted';
        $reviewer = $report->reviewer;

        return [
            'review_status' => $status,
            'review_status_label' => ucfirst(str_replace('_', ' ', $status)),
            'review_comments' => $report->review_comments,
            'reviewed_at' => optional($report->reviewed_at)?->toIso8601String(),
            'reviewed_by_name' => trim(($reviewer?->first_name ?? '') . ' ' . ($reviewer?->last_name ?? '')),
        ];
    }

    private function deriveEquipmentAvailabilityStatus(EquipmentToStation $assignment): string
    {
        if (($assignment->available_quantity ?? 0) > 0) {
            return 'available';
        }

        if (($assignment->maintenance_quantity ?? 0) > 0) {
            return 'maintenance';
        }

        if (($assignment->damaged_quantity ?? 0) > 0) {
            return 'damaged';
        }

        return 'in-use';
    }

    private function perPage(Request $request): int
    {
        return max(5, min(50, $request->integer('per_page', 10)));
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => max(1, $paginator->lastPage()),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * Update incident
     */
    public function updateIncident(Request $request, $id)
    {
        try {
            $incident = Incident::findOrFail($id);

            if ($request->input('entry_mode') === 'historical_fire_record' || $incident->historicalRecords()->exists()) {
                return $this->updateHistoricalFireIncident($request, $incident);
            }
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'priority' => 'sometimes|string|in:low,medium,high,critical',
                'status' => 'sometimes|string|in:reported,responding,active,resolved',
                'lat' => 'sometimes|numeric',
                'lng' => 'sometimes|numeric',
                'reported_by' => 'sometimes|string|max:255',
                'reporter_contact' => 'sometimes|string|max:20',
                'station_id' => 'sometimes|nullable|exists:stations,station_id'
            ]);
            
            // Update marker if exists
            if ($incident->marker && (isset($validated['title']) || isset($validated['description']) || isset($validated['lat']) || isset($validated['lng']))) {
                $markerData = [];
                if (isset($validated['title'])) $markerData['title'] = $validated['title'];
                if (isset($validated['description'])) $markerData['description'] = $validated['description'];
                if (isset($validated['lat'])) $markerData['lat'] = $validated['lat'];
                if (isset($validated['lng'])) $markerData['lng'] = $validated['lng'];
                $incident->marker->update($markerData);
            }
            
            // Update incident
            if (isset($validated['priority'])) {
                $priorityMap = [
                    'low' => 1,
                    'medium' => 2,
                    'high' => 3,
                    'critical' => 4
                ];
                $incident->incident_type_id = $priorityMap[$validated['priority']] ?? 2;
            }
            
            if (isset($validated['status'])) {
                $statusMap = [
                    'reported' => 1,
                    'responding' => 2,
                    'active' => 3,
                    'resolved' => 4
                ];
                $incident->incident_status_id = $statusMap[$validated['status']] ?? 1;
            }
            
            if (isset($validated['reported_by'])) $incident->reported_by = $validated['reported_by'];
            if (isset($validated['station_id'])) $incident->station_id = $validated['station_id'];
            
            $incident->save();
            UserActivityLogger::log('updated incident', Auth::user()?->user_id, $request);

            // If incident is controlled/resolved, release team and station officers to available.
            if ((int)$incident->incident_status_id === 4 && $incident->team_id) {
                $team = Team::find($incident->team_id);
                if ($team) {
                    $team->update(['status' => 'available', 'updated_at' => now()]);

                    $teamMemberUserIds = collect([
                        $team->commander_driver,
                        $team->pumper,
                        $team->team_leader,
                        $team->firefighter_officer_1,
                        $team->firefighter_officer_2,
                        $team->firefighter_officer_3,
                        $team->rescue_radio,
                    ])->filter()->unique()->values();

                    if ($teamMemberUserIds->isNotEmpty()) {
                        StationOfficer::whereIn('user_id', $teamMemberUserIds->all())
                            ->update(['status' => 'available', 'updated_at' => now()]);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Incident updated successfully',
                'data' => $incident->load(['marker', 'incidentType', 'incidentStatus'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update incident: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function updateHistoricalFireIncident(Request $request, Incident $incident)
    {
        $stationOfficer = $this->currentStationOfficer();

        if (! $stationOfficer?->station_id) {
            return response()->json([
                'success' => false,
                'message' => 'No station assignment found for this account.'
            ], 422);
        }

        $historical = $incident->historicalRecords()->latest('historical_fire_incident_id')->first();

        if (! $historical) {
            return response()->json([
                'success' => false,
                'message' => 'Historical fire record not found for this incident.'
            ], 404);
        }

        $managedBarangayIds = $this->getManagedBarangays($stationOfficer->station_id)
            ->pluck('barangay_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        if (empty($managedBarangayIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No barangays could be resolved from the assigned station address. Update the station municipality and barangay first.',
            ], 422);
        }

        $validated = $request->validate([
            'entry_mode' => 'nullable|in:historical_fire_record',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'incident_date' => 'sometimes|required|date|before_or_equal:today',
            'incident_time' => 'nullable|date_format:H:i',
            'barangay_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::in($managedBarangayIds),
            ],
            'incident_location' => 'sometimes|required|string|max:255',
            'fire_classification' => 'sometimes|required|in:accidental,intentional,undetermined',
            'fire_cause' => 'nullable|string|max:255',
            'incident_count' => 'nullable|integer|min:1|max:9999',
            'injured_count' => 'nullable|integer|min:0|max:9999',
            'death_count' => 'nullable|integer|min:0|max:9999',
            'estimated_damage' => 'nullable|numeric|min:0',
            'lat' => 'sometimes|required|numeric|between:-90,90',
            'lng' => 'sometimes|required|numeric|between:-180,180',
            'source_name' => 'nullable|string|max:255',
            'source_reference' => 'nullable|string|max:255',
            'weather_condition' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric|min:0|max:100',
            'wind_speed' => 'nullable|numeric|min:0',
            'wind_direction' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $incident, $historical, $request) {
            if ($incident->marker) {
                $markerPayload = [];
                if (array_key_exists('title', $validated)) {
                    $markerPayload['title'] = $validated['title'];
                }
                if (array_key_exists('description', $validated)) {
                    $markerPayload['description'] = $validated['description'] ?: ($validated['incident_location'] ?? $historical->incident_location);
                }
                if (array_key_exists('lat', $validated)) {
                    $markerPayload['lat'] = $validated['lat'];
                }
                if (array_key_exists('lng', $validated)) {
                    $markerPayload['lng'] = $validated['lng'];
                }
                if (! empty($markerPayload)) {
                    $incident->marker->update($markerPayload);
                }
            }

            $historical->fill([
                'barangay_id' => $validated['barangay_id'] ?? $historical->barangay_id,
                'incident_date' => $validated['incident_date'] ?? $historical->incident_date,
                'incident_time' => array_key_exists('incident_time', $validated)
                    ? ($validated['incident_time'] ? $validated['incident_time'] . ':00' : null)
                    : $historical->incident_time,
                'source_name' => $validated['source_name'] ?? $historical->source_name,
                'source_reference' => $validated['source_reference'] ?? $historical->source_reference,
                'fire_classification' => $validated['fire_classification'] ?? $historical->fire_classification,
                'fire_cause' => $validated['fire_cause'] ?? $historical->fire_cause,
                'incident_location' => $validated['incident_location'] ?? $historical->incident_location,
                'incident_count' => array_key_exists('incident_count', $validated) ? (int) $validated['incident_count'] : $historical->incident_count,
                'injured_count' => array_key_exists('injured_count', $validated) ? (int) $validated['injured_count'] : $historical->injured_count,
                'death_count' => array_key_exists('death_count', $validated) ? (int) $validated['death_count'] : $historical->death_count,
                'estimated_damage' => array_key_exists('estimated_damage', $validated) ? $validated['estimated_damage'] : $historical->estimated_damage,
                'temperature' => array_key_exists('temperature', $validated) ? $validated['temperature'] : $historical->temperature,
                'humidity' => array_key_exists('humidity', $validated) ? $validated['humidity'] : $historical->humidity,
                'wind_speed' => array_key_exists('wind_speed', $validated) ? $validated['wind_speed'] : $historical->wind_speed,
                'wind_direction' => $validated['wind_direction'] ?? $historical->wind_direction,
                'weather_condition' => $validated['weather_condition'] ?? $historical->weather_condition,
                'notes' => $validated['notes'] ?? $historical->notes,
            ]);
            $historical->save();

            if (array_key_exists('barangay_id', $validated)) {
                $incident->barangay_id = (int) $validated['barangay_id'];
            }

            if (array_key_exists('incident_date', $validated) || array_key_exists('incident_time', $validated)) {
                $incidentDate = $validated['incident_date'] ?? optional($historical->incident_date)->toDateString() ?? optional($incident->created_at)->toDateString();
                $incidentTime = array_key_exists('incident_time', $validated)
                    ? ($validated['incident_time'] ?: '00:00')
                    : (optional($historical->incident_time)->format('H:i') ?: optional($incident->occurred_at)->format('H:i') ?: '00:00');
                IncidentOccurrenceSynchronizer::verify(
                    $incident,
                    Carbon::parse(trim($incidentDate . ' ' . $incidentTime)),
                    'historical_bfp_record',
                    Auth::user()?->user_id
                );
            }

            $incident->status = 'Resolved';
            $incident->incident_status_id = 4;
            $incident->save();

            IncidentContextDispatcher::dispatchFor($incident);
            UserActivityLogger::log('updated historical fire incident', Auth::user()?->user_id, $request);
        });

        return response()->json([
            'success' => true,
            'message' => 'Historical fire record updated successfully.',
            'data' => $incident->fresh()->load(['marker', 'reporter', 'historicalRecords.barangay'])
        ]);
    }

    /**
     * Update personnel
     */
    public function updatePersonnel(Request $request, $id)
    {
        try {
            $personnel = User::findOrFail($id);
            
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'rank' => 'sometimes|string|max:100',
                'account_code' => 'sometimes|string|max:50',
                'contact_number' => 'sometimes|string|max:20',
                'email' => 'sometimes|email|max:255',
                'status' => 'sometimes|string|in:active,inactive'
            ]);
            
            if (isset($validated['first_name'])) $personnel->first_name = $validated['first_name'];
            if (isset($validated['last_name'])) $personnel->last_name = $validated['last_name'];
            if (isset($validated['email'])) $personnel->email = $validated['email'];
            if (isset($validated['account_code'])) $personnel->account_code = $validated['account_code'];
            if (isset($validated['contact_number'])) $personnel->contact_number = $validated['contact_number'];
            
            if (isset($validated['status'])) {
                $personnel->status_id = $validated['status'] === 'active' ? 1 : 0;
            }
            
            $personnel->save();
            UserActivityLogger::log('updated personnel', Auth::user()?->user_id, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Personnel updated successfully',
                'data' => $personnel->load('role')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update personnel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update equipment
     */
    public function updateEquipment(Request $request, $id)
    {
        try {
            $equipment = Equipment::findOrFail($id);
            
            $validated = $request->validate([
                'equipment_name' => 'sometimes|string|max:255',
                'type' => 'sometimes|string|max:100',
                'serial_number' => 'sometimes|string|max:100',
                'model' => 'sometimes|string|max:100',
                'status' => 'sometimes|string|in:available,in-use,maintenance,damaged',
                'equipment_category_id' => 'sometimes|exists:equipment_categories,equipment_category_id'
            ]);
            
            if (isset($validated['equipment_name'])) $equipment->equipment_name = $validated['equipment_name'];
            if (isset($validated['type'])) $equipment->type = $validated['type'];
            if (isset($validated['serial_number'])) $equipment->serial_number = $validated['serial_number'];
            if (isset($validated['model'])) $equipment->model = $validated['model'];
            if (isset($validated['status'])) $equipment->status = $validated['status'];
            if (isset($validated['equipment_category_id'])) $equipment->equipment_category_id = $validated['equipment_category_id'];
            
            $equipment->save();
            UserActivityLogger::log('updated equipment', Auth::user()?->user_id, $request);
            
            return response()->json([
                'success' => true,
                'message' => 'Equipment updated successfully',
                'data' => $equipment->load(['category', 'stationEquipments'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update equipment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an item
     */
    public function destroy($type, $id)
    {
        try {
            switch($type) {
                case 'incidents':
                    $item = Incident::findOrFail($id);
                    // Delete associated marker if exists
                    if ($item->marker) {
                        $item->marker->delete();
                    }
                    $item->delete();
                    break;
                case 'personnel':
                    $item = User::findOrFail($id);
                    $item->delete();
                    break;
                case 'equipment':
                    $item = Equipment::findOrFail($id);
                    $item->delete();
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid item type'
                    ], 400);
            }
            UserActivityLogger::log('deleted ' . rtrim($type, 's'), Auth::user()?->user_id, request());
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }
}
