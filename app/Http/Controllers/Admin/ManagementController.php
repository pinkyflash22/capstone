<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Marker;

use App\Models\Station;
use App\Models\Equipment;

use App\Models\EquipmentToStation;
use App\Models\Team;
use App\Models\StationOfficer;
use App\Models\User;
use App\Models\Role;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ManagementController extends Controller
{
      private $roleCodes = [
        'citizen' => 'CIT',
        'admin' => 'ADM',
        'firefighter' => 'BFP',
        'chief' => 'FMC',
        'dispatcher' => 'DSP',
        'paramedic' => 'PAR',
        'investigator' => 'INV',
    ];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get all necessary data
   
        $mapmarker = Marker::with('stations')->get();
        
        // Get incidents with relations
        $incidents = Incident::with(['marker','reporter'])->get();
        
        // Get alerts (reported incidents)
        $alerts = Incident::with('marker')->where('status', 'Reported')->get();
        
        // Get stations
        $stations = Station::all();
        $provinces = Province::orderBy('province_name')->get();
        
        // Get chiefs (users with role 'chief')
        $chiefRole = Role::where('role', 'chief')->first();
        $chiefs = $chiefRole ? User::where('role_id', $chiefRole->role_id)->get() : collect();
        
        // Get personnel (users with role 'firefighter' or 'personnel')
        $personnelRole = Role::whereIn('role', ['firefighter', 'admin', 'chief'])->get();
        $personnel = User::with(['role'])
            ->whereIn('role_id', $personnelRole->pluck('role_id'))
            ->where('role_id','!=','5')
            ->get();
        
        // Get equipment with relations
        $equipment = Equipment::with([ 'stationEquipments.station'])->get();
        
        
         $roles = Role::where('role', '!=', 'anonymous')
            ->orderBy('role')
            ->get();

        $totalIncidents = $incidents;
        
        return view('admin.management', compact(
            'totalIncidents',
 
            'mapmarker',
            'incidents',
            'alerts',
            'stations',
            'provinces',
            'chiefs',
            'personnel',
            'equipment',
    
            'roles'
        ));
    }

    /**
     * Get incidents data for API with filters
     */
    public function getIncidents(Request $request)
    {
        $query = Incident::with(['marker', 'reporter']);
        
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
            'data' => $incidents
        ]);
    }

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
        $personnelRole = Role::whereIn('role', ['firefighter', 'admin', 'chief','citizen'])->get();
        $query = User::with(['role'])
            ->whereIn('role_id', $personnelRole->pluck('role_id'));
        
        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('account_code', 'LIKE', "%{$search}%");
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
                $query->where('status_id', $statusId);
            }
        }
        
        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $personnel = $query->get();
        
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
        $query = Equipment::with(['category', 'stationEquipments.station']);
        
        // Apply search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('equipment_name', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%")
                  ->orWhere('serial_number', 'LIKE', "%{$search}%");
            });
        }
        
        // Apply status filter
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Apply sorting
        $sortBy = $request->get('sort_by', 'equipment_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);
        
        $equipment = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $equipment
        ]);
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
