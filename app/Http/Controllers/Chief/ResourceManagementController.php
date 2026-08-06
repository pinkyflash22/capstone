<?php

namespace App\Http\Controllers\Chief;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Equipment;
use App\Models\EquipmentToStation;
use App\Models\Station;
use App\Models\User;
use App\Models\Role;
use App\Models\StationOfficer;
use App\Models\Team;
use App\Models\Province;
use App\Models\Barangay;
use App\Models\BoundaryLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;



class ResourceManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loggedInChief = Auth::user()?->loadMissing('address.barangay.city.province');
        $thisUser = $loggedInChief?->user_id;
        //direct to station officer
        $thisStation = StationOfficer::where('user_id', $thisUser)->first();
        //ito na ung station nya mismo
        $stationExact = $thisStation?->station_id;

       
        $equipments = Equipment::all();
      
        $stationOfficers = StationOfficer::where('station_id',$stationExact)->with('user.role','station')->get();

        $activeStations = Station::where('station_id', $stationExact)
            ->with(['marker', 'address.barangay.city.province'])
            ->get();
        $currentStation = $activeStations->first();
        $chiefCityId = $loggedInChief?->address?->barangay?->city?->city_id
            ?? $currentStation?->address?->barangay?->city?->city_id;
        $chiefProvinceId = $loggedInChief?->address?->barangay?->city?->province?->province_id
            ?? $currentStation?->address?->barangay?->city?->province?->province_id;
        $citizenRoleId = Role::where('role', 'citizen')->value('role_id');

        // $allTeam = Team::with('commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio')->get();

        $thisTeam = Team::with('teamLeader')->get();

        $stationUserIds = StationOfficer::where('station_id', $stationExact)->pluck('user_id');
        $allTeam = Team::where(function ($query) use ($stationUserIds) {
            $query->whereIn('team_leader', $stationUserIds)
                ->orWhereIn('commander_driver', $stationUserIds)
                ->orWhereIn('pumper', $stationUserIds)
                ->orWhereIn('firefighter_officer_1', $stationUserIds)
                ->orWhereIn('firefighter_officer_2', $stationUserIds)
                ->orWhereIn('firefighter_officer_3', $stationUserIds)
                ->orWhereIn('rescue_radio', $stationUserIds);
        })->with(['commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio'])->get();

        
        

        //dit osa all team dapat ung belong lang sa station nato dadaan ulit kay team to station offcier to get station_id
        $users = User::where('role_id', '!=', $citizenRoleId)->with('role', 'address')->get();
        $allusers = User::where(function ($query) use ($stationUserIds, $citizenRoleId) {
            $query->whereIn('user_id', $stationUserIds)
                ->orWhere('role_id', $citizenRoleId);
        })->with('role', 'address')->get(); // includes station personnel used by chief plus citizens for system-user modals
        // $userOfficer = StationOfficer::where('s', $all);
        $citizens = User::where('role_id', $citizenRoleId)->with('role', 'address')->get();
        //user_id ulit tanggalin ung role na admin, firefighter,chief, station admin na hnd belogn sa station 1
        //need padin include ung citizen e
        $roles = Role::all();
        $provinces = Province::orderBy('province_name')->get();
        $boundaryBarangays = $this->buildBoundaryBarangayList($stationExact, $chiefCityId);
        
        
        return view('chief.resource-management', compact(
            'citizens',
            'allusers',
            'provinces',
            'roles',
            'equipments',
            'activeStations',
            'users',
            'stationOfficers',
            'allTeam',
            'currentStation',
            'boundaryBarangays',
            'chiefCityId',
            'chiefProvinceId'
        ));
    }

    protected function buildBoundaryBarangayList(?int $stationId, ?int $chiefCityId = null)
    {
        $supportsBarangayBoundaries = Schema::hasColumn('boundary_location', 'barangay_id')
            && Schema::hasColumn('boundary_location', 'boundary_type');

        $station = Station::query()
            ->with($supportsBarangayBoundaries
                ? ['address.barangay.city', 'barangays.city', 'barangays.boundaryLocations']
                : ['address.barangay.city', 'barangays.city'])
            ->find($stationId);

        $cityId = $chiefCityId ?: $station?->address?->barangay?->city?->city_id;

        $barangays = $station?->barangays;

        if ($barangays && $cityId) {
            $barangays = $barangays->filter(fn (Barangay $barangay) => (int) $barangay->city_id === (int) $cityId);
        }

        if (! $barangays || $barangays->isEmpty()) {
            $barangays = Barangay::query()
                ->with($supportsBarangayBoundaries ? ['city', 'boundaryLocations'] : ['city'])
                ->when($cityId, fn ($query) => $query->where('city_id', $cityId))
                ->orderBy('barangay_name')
                ->get();
        }

        return $barangays
            ->sortBy('barangay_name')
            ->values()
            ->map(function (Barangay $barangay) {
                $boundary = null;

                if (isset($barangay->boundaryLocations)) {
                    $boundary = $barangay->boundaryLocations
                        ->where('boundary_type', 'barangay')
                        ->sortByDesc('updated_at')
                        ->first();
                }

                $boundaryState = $this->buildBoundaryState($barangay, $boundary);

                return [
                    'barangay_id' => $barangay->barangay_id,
                    'barangay_name' => $barangay->barangay_name,
                    'city_name' => $barangay->city?->city_name,
                    'has_boundary' => $boundaryState['has_boundary'],
                    'boundary_is_valid' => $boundaryState['is_valid'],
                    'boundary_status' => $boundaryState['status'],
                    'boundary_status_label' => $boundaryState['label'],
                    'boundary_status_icon' => $boundaryState['icon'],
                    'boundary_source' => $boundary?->source,
                    'boundary_updated_at' => $boundary?->updated_at,
                    'boundary_updated_at_human' => $boundary?->updated_at?->diffForHumans(),
                    'boundary_name' => $boundary?->name,
                    'external_place_id' => $boundary?->external_place_id,
                ];
            });
    }

    protected function buildBoundaryState(Barangay $barangay, ?BoundaryLocation $boundary): array
    {
        if (! $boundary) {
            return [
                'has_boundary' => false,
                'is_valid' => false,
                'status' => 'missing',
                'label' => 'Needs Sync',
                'icon' => 'fa-clock',
            ];
        }

        $properties = (array) ($boundary->properties ?? []);
        $geoapifyFeature = (array) ($properties['geoapify_feature'] ?? []);
        $geoapifyName = trim((string) ($geoapifyFeature['name'] ?? ''));
        $expectedBarangay = trim((string) $barangay->barangay_name);
        $expectedCity = trim((string) ($barangay->city?->city_name ?? ''));

        if (
            $boundary->source === 'geoapify'
            && $geoapifyName !== ''
            && strcasecmp($geoapifyName, $expectedBarangay) !== 0
            && ($expectedCity === '' || strcasecmp($geoapifyName, $expectedCity) === 0)
        ) {
            return [
                'has_boundary' => true,
                'is_valid' => false,
                'status' => 'invalid',
                'label' => 'Invalid Boundary',
                'icon' => 'fa-triangle-exclamation',
            ];
        }

        return [
            'has_boundary' => true,
            'is_valid' => true,
            'status' => 'synced',
            'label' => 'Boundary Ready',
            'icon' => 'fa-circle-check',
        ];
    }

    public function assignEquipmentToStation(Request $request)
    {
        try {
            $stationOfficer = StationOfficer::where('user_id', Auth::user()->user_id)->firstOrFail();

            $validated = $request->validate([
                'equipment_id' => 'required|exists:equipments,equipment_id',
                'available_quantity' => 'required|integer|min:1',
            ]);

            $assignment = EquipmentToStation::firstOrNew([
                'station_id' => $stationOfficer->station_id,
                'equipment_id' => $validated['equipment_id'],
            ]);

            $assignment->available_quantity = (int) ($assignment->available_quantity ?? 0) + (int) $validated['available_quantity'];
            $assignment->damaged_quantity = (int) ($assignment->damaged_quantity ?? 0);
            $assignment->maintenance_quantity = (int) ($assignment->maintenance_quantity ?? 0);
            $assignment->save();

            return response()->json([
                'success' => true,
                'message' => 'Equipment assigned successfully.',
                'data' => $assignment->load(['station', 'equipment.category']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign equipment: ' . $e->getMessage(),
            ], 500);
        }
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
