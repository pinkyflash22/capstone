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
use App\Models\Role;
use App\Models\Province;
use App\Models\SpotInvestigationReport;
use App\Models\ProgressInvestigationReport;
use App\Models\FinalInvestigationReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\UserActivityLogger;
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $thisUser = Auth::user()->user_id;
        $thisStation = StationOfficer::where('user_id', $thisUser)->first();
        $stationExact = $thisStation?->station_id;

    
        $mapmarker = Marker::with('stations')->get();

        $stations = Station::where('station_id', $stationExact)->with(['marker'])->get();
        $stationOfficers = StationOfficer::with(['user.role', 'station'])
            ->where('station_id', $stationExact)
            ->get();
        $stationOfficerIds = $stationOfficers->pluck('station_officer_id')->filter();
        $stationOfficerUserIds = $stationOfficers->pluck('user_id')->filter();
        $stationTeamIds = Team::whereIn('team_leader', $stationOfficerUserIds)->pluck('team_id');

        $incidents = Incident::whereIn('team_id', $stationTeamIds)
            ->with(['marker', 'reporter', 'team'])
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

        $reports = $this->buildStationReports($stationOfficerIds->all());
  
        $roles = Role::where('role', '!=', 'anonymous')
            ->orderBy('role')
            ->get();

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
            'roles'
        ));
    }

    public function updateStation(Request $request, Station $station)
    {
        $currentUserId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::where('user_id', $currentUserId)->first();

        if (!$stationOfficer || (int) $stationOfficer->station_id !== (int) $station->station_id) {
            abort(403, 'You are not allowed to update this station.');
        }

        if (strtolower(Auth::user()->role->role ?? '') !== 'chief') {
            abort(403, 'Only chiefs can update station information.');
        }

        $validated = $request->validate([
            'station_name' => 'required|string|max:255',
            'station_code' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $station->update($validated);
        UserActivityLogger::log('updated station information', auth()->user()?->user_id, $request);

        return response()->json([
            'success' => true,
            'message' => 'Station information updated successfully.',
            'data' => $station->fresh('marker'),
        ]);
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
public function getIncidents(Request $request)
{
    $accountId = Auth::user()->user_id;
    $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
    $stationUserIds = StationOfficer::where('station_id', $stationOfficer?->station_id)
        ->pluck('user_id')
        ->filter()
        ->toArray();
    $stationTeamIds = Team::whereIn('team_leader', $stationUserIds)
        ->pluck('team_id')
        ->toArray();

    if (empty($stationTeamIds)) {
        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'No station teams found for this commander'
        ]);
    }

    $query = Incident::with(['marker', 'reporter'])
        ->whereIn('team_id', $stationTeamIds);
    
    // Apply search filter
    if ($request->has('search') && $request->search) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('incident_id', 'LIKE', "%{$search}%")
              ->orWhere('reported_by', 'LIKE', "%{$search}%")
              ->orWhereHas('marker', function($sub) use ($search) {
                  $sub->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
              });
        });
    }
    
    // Apply status filter
    if ($request->has('status') && $request->status) {
        $query->where('status', $request->status);
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
                $query->whereMonth('created_at', Carbon::now()->month);
                break;
        }
    }
    
    // Apply sorting
    $sortBy = $request->get('sort_by', 'created_at');
    $sortOrder = $request->get('sort_order', 'desc');
    $query->orderBy($sortBy, $sortOrder);
    
    $incidents = $query->get();
    
    return response()->json([
        'success' => true,
        'data' => $incidents,
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
    public function getPersonnel(Request $request)
    {
        $accountId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
        $query = StationOfficer::with(['user.role'])
            ->where('station_id', $stationOfficer?->station_id);
        
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
        if ($request->has('status') && $request->status) {
            $statusMap = [
                'active' => 1,
                'inactive' => 0
            ];
            $statusId = $statusMap[$request->status] ?? null;
            if ($statusId !== null) {
                $query->whereHas('user', function ($userQuery) use ($statusId) {
                    $userQuery->where('status_id', $statusId);
                });
            }
        }

        $personnel = $query->get()->map(function ($officer) {
            return [
                'user_id' => $officer->user?->user_id,
                'station_officer_id' => $officer->station_officer_id,
                'first_name' => $officer->user?->first_name,
                'last_name' => $officer->user?->last_name,
                'email' => $officer->user?->email,
                'account_code' => $officer->user?->account_code,
                'contact_number' => $officer->user?->contact_number,
                'status_id' => $officer->user?->status_id,
                'rank' => $officer->rank,
                'role' => $officer->user?->role ? [
                    'role_id' => $officer->user->role->role_id,
                    'role' => $officer->user->role->role,
                ] : null,
            ];
        })->sortBy([
            ['first_name', 'asc'],
            ['last_name', 'asc'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => $personnel
        ]);
    }

    /**
     * Get equipment data for API
     */
    public function getEquipment(Request $request)
    {
        $accountId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
        $query = EquipmentToStation::with(['equipment.category', 'station'])
            ->where('station_id', $stationOfficer?->station_id);
        
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

        $equipment = $query->get()->map(function ($assignment) {
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
        })->sortBy('equipment_name')->values();

        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
    }

    public function getReports(Request $request)
    {
        $accountId = Auth::user()->user_id;
        $stationOfficer = StationOfficer::where('user_id', $accountId)->first();
        $stationOfficerIds = StationOfficer::where('station_id', $stationOfficer?->station_id)
            ->pluck('station_officer_id')
            ->filter()
            ->all();

        $reports = collect($this->buildStationReports($stationOfficerIds));

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $reports = $reports->filter(function ($report) use ($search) {
                return str_contains(strtolower(($report['title'] ?? '') . ' ' . ($report['incident_title'] ?? '') . ' ' . ($report['investigator_name'] ?? '')), $search);
            });
        }

        if ($request->filled('status')) {
            $reports = $reports->where('report_kind', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $reports->values(),
        ]);
    }

    private function buildStationReports(array $stationOfficerIds)
    {
        $spotReports = SpotInvestigationReport::with(['incident.marker', 'incident.reporter', 'investigator.user', 'progressInvestigation.finalInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
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
                ];
            });

        $progressReports = ProgressInvestigationReport::with(['spotInvestigation.incident.marker', 'investigator.user', 'fireOrigin', 'finalInvestigation'])
            ->whereIn('station_officer_id', $stationOfficerIds)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => 'progress-' . $report->progress_investigation_report_id,
                    'report_id' => $report->progress_investigation_report_id,
                    'report_kind' => 'progress',
                    'title' => 'Progress Investigation Report',
                    'type' => 'Progress Investigation',
                    'incident_id' => $report->spotInvestigation?->incident_id,
                    'incident_title' => $report->spotInvestigation?->incident?->marker?->title ?? ('Incident #' . ($report->spotInvestigation?->incident_id ?? '')),
                    'investigator_name' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'author' => trim(($report->investigator?->user?->first_name ?? '') . ' ' . ($report->investigator?->user?->last_name ?? '')),
                    'status' => $report->finalInvestigation ? 'finalized' : 'conducting',
                    'created_at' => optional($report->created_at)?->toIso8601String(),
                    'description' => $report->fire_cause,
                ];
            });

        $finalReports = FinalInvestigationReport::with(['progressInvestigation.spotInvestigation.incident.marker', 'progressInvestigation.investigator.user'])
            ->whereIn('station_officer_id', $stationOfficerIds)
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
                ];
            });

        return $spotReports
            ->concat($progressReports)
            ->concat($finalReports)
            ->sortByDesc('created_at')
            ->values();
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

    /**
     * Update incident
     */
    public function updateIncident(Request $request, $id)
    {
        try {
            $incident = Incident::findOrFail($id);
            
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
