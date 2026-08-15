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
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;



class ResourceManagementController extends Controller
{
    protected function currentRole(): string
    {
        return strtolower(trim((string) (Auth::user()?->role?->role ?? '')));
    }

    protected function isProvincialAdmin(): bool
    {
        return $this->currentRole() === 'provincial admin';
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($this->isProvincialAdmin()) {
            $cacheKey = 'resource-management:provincial:v3';
            $equipments = Cache::remember("{$cacheKey}:equipment", now()->addSeconds(60), fn () => Equipment::orderBy('equipment_name')->get());
            $stationOfficers = Cache::remember("{$cacheKey}:officers", now()->addSeconds(60), fn () => StationOfficer::with('user.role', 'station')->orderBy('station_officer_id')->get());
            $activeStations = Cache::remember("{$cacheKey}:stations", now()->addMinutes(5), fn () => Station::with(['marker', 'address.barangay.city.province'])->orderBy('station_name')->get());
            $currentStation = null;
            $chiefCityId = null;
            $chiefProvinceId = null;
            $citizenRoleId = Role::where('role', 'citizen')->value('role_id');
            $stationUserIds = StationOfficer::query()->pluck('user_id');

            $teamRelations = $this->teamRelations();
            $allTeam = Cache::remember("{$cacheKey}:teams", now()->addSeconds(45), fn () => Team::with($teamRelations)->orderByDesc('team_id')->get());
            $allusers = Cache::remember("{$cacheKey}:users", now()->addSeconds(45), fn () => User::with('role', 'address')->orderBy('last_name')->orderBy('first_name')->get());
            $users = $allusers->where('role_id', '!=', $citizenRoleId)->values();
            $citizens = $allusers->where('role_id', $citizenRoleId)->values();
            $roles = Cache::remember('resource-management:roles:v1', now()->addMinutes(10), fn () => Role::orderBy('role')->get());
            $provinces = Cache::remember('resource-management:provinces:v1', now()->addMinutes(10), fn () => Province::orderBy('province_name')->get());
            $boundaryBarangays = Cache::remember("{$cacheKey}:boundaries", now()->addSeconds(60), fn () => $this->buildBoundaryBarangayList(null, null));
            $systemUsers = $allusers;
            $teamData = $this->serializeTeams($allTeam);
            [$filteredOfficers, $filteredTeams, $filteredUsers, $filteredEquipment, $filteredBoundaries] = $this->filterTableCollections(
                $request, $stationOfficers, $allTeam, $systemUsers, $equipments, $boundaryBarangays
            );
            [$personnelPage, $teamsPage, $usersPage, $equipmentPage, $boundaryPage] = $this->buildTablePages(
                $filteredOfficers, $filteredTeams, $filteredUsers, $filteredEquipment, $filteredBoundaries
            );

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
                'chiefProvinceId',
                'personnelPage',
                'teamsPage',
                'usersPage',
                'equipmentPage',
                'boundaryPage',
                'systemUsers',
                'teamData'
            ));
        }

        $loggedInChief = Auth::user()?->loadMissing('address.barangay.city.province');
        $thisUser = $loggedInChief?->user_id;
        //direct to station officer
        $thisStation = StationOfficer::where('user_id', $thisUser)->first();
        //ito na ung station nya mismo
        $stationExact = $thisStation?->station_id;

       
        $cacheKey = "resource-management:station:{$stationExact}:v3";
        $equipments = Cache::remember("{$cacheKey}:equipment", now()->addSeconds(60), fn () => Equipment::orderBy('equipment_name')->get());
      
        $stationOfficers = Cache::remember("{$cacheKey}:officers", now()->addSeconds(45), fn () => StationOfficer::where('station_id', $stationExact)
            ->with('user.role', 'station')
            ->orderBy('station_officer_id')
            ->get());

        $activeStations = Cache::remember("{$cacheKey}:stations", now()->addMinutes(5), fn () => Station::where('station_id', $stationExact)
            ->with(['marker', 'address.barangay.city.province'])
            ->get());
        $currentStation = $activeStations->first();
        $chiefCityId = $loggedInChief?->address?->barangay?->city?->city_id
            ?? $currentStation?->address?->barangay?->city?->city_id;
        $chiefProvinceId = $loggedInChief?->address?->barangay?->city?->province?->province_id
            ?? $currentStation?->address?->barangay?->city?->province?->province_id;
        $citizenRoleId = Role::where('role', 'citizen')->value('role_id');

        // $allTeam = Team::with('commander', 'pumpers', 'teamLeader', 'officer1', 'officer2', 'officer3', 'rescueRadio')->get();

        $thisTeam = Team::with('teamLeader')->get();

        $stationUserIds = $stationOfficers->pluck('user_id');
        $stationOfficerIds = $stationOfficers->pluck('station_officer_id');
        $teamRelations = $this->teamRelations();
        $allTeam = Cache::remember("{$cacheKey}:teams", now()->addSeconds(45), function () use ($stationExact, $stationOfficerIds, $teamRelations) {
            return Team::query()
                ->where(function ($query) use ($stationExact, $stationOfficerIds) {
                    if (Schema::hasColumn('teams', 'station_id')) {
                        $query->where('station_id', $stationExact);
                    }
                    foreach (['team_leader', 'commander_driver', 'pumper', 'firefighter_officer_1', 'firefighter_officer_2', 'firefighter_officer_3', 'rescue_radio'] as $column) {
                        $query->orWhereIn($column, $stationOfficerIds);
                    }
                })
                ->with($teamRelations)
                ->orderByDesc('team_id')
                ->get();
        });

        
        

        //dit osa all team dapat ung belong lang sa station nato dadaan ulit kay team to station offcier to get station_id
        $allusers = Cache::remember("{$cacheKey}:users", now()->addSeconds(45), fn () => User::where(function ($query) use ($stationUserIds, $citizenRoleId) {
            $query->whereIn('user_id', $stationUserIds)->orWhere('role_id', $citizenRoleId);
        })->with('role', 'address')->orderBy('last_name')->orderBy('first_name')->get());
        $users = $allusers->where('role_id', '!=', $citizenRoleId)->values();
        // $userOfficer = StationOfficer::where('s', $all);
        $citizens = $allusers->where('role_id', $citizenRoleId)->values();
        //user_id ulit tanggalin ung role na admin, firefighter,chief, station admin na hnd belogn sa station 1
        //need padin include ung citizen e
        $roles = Cache::remember('resource-management:roles:v1', now()->addMinutes(10), fn () => Role::orderBy('role')->get());
        $provinces = Cache::remember('resource-management:provinces:v1', now()->addMinutes(10), fn () => Province::orderBy('province_name')->get());
        $boundaryBarangays = Cache::remember("{$cacheKey}:boundaries", now()->addSeconds(60), fn () => $this->buildBoundaryBarangayList($stationExact, $chiefCityId));
        $systemUsers = $allusers->concat($citizens)->unique('user_id')->values();
        $teamData = $this->serializeTeams($allTeam);
        [$filteredOfficers, $filteredTeams, $filteredUsers, $filteredEquipment, $filteredBoundaries] = $this->filterTableCollections(
            $request, $stationOfficers, $allTeam, $systemUsers, $equipments, $boundaryBarangays
        );
        [$personnelPage, $teamsPage, $usersPage, $equipmentPage, $boundaryPage] = $this->buildTablePages(
            $filteredOfficers, $filteredTeams, $filteredUsers, $filteredEquipment, $filteredBoundaries
        );
        
        
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
            'chiefProvinceId',
            'personnelPage',
            'teamsPage',
            'usersPage',
            'equipmentPage',
            'boundaryPage',
            'systemUsers',
            'teamData'
        ));
    }

    protected function teamRelations(): array
    {
        return [
            'commander.user.role',
            'pumpers.user.role',
            'teamLeader.user.role',
            'officer1.user.role',
            'officer2.user.role',
            'officer3.user.role',
            'rescueRadio.user.role',
        ];
    }

    protected function serializeTeams(Collection $teams): array
    {
        return $teams->map(fn (Team $team) => [
            'team_id' => $team->team_id,
            'station_id' => $team->station_id ?? $team->teamLeader?->station_id,
            'status' => $team->status,
            'team_leader' => $team->getRawOriginal('team_leader'),
            'commander_driver' => $team->getRawOriginal('commander_driver'),
            'pumper' => $team->getRawOriginal('pumper'),
            'firefighter_officer_1' => $team->getRawOriginal('firefighter_officer_1'),
            'firefighter_officer_2' => $team->getRawOriginal('firefighter_officer_2'),
            'firefighter_officer_3' => $team->getRawOriginal('firefighter_officer_3'),
            'rescue_radio' => $team->getRawOriginal('rescue_radio'),
            'teamLeader' => $team->teamLeader,
            'commander' => $team->commander,
            'pumpers' => $team->pumpers,
            'officer1' => $team->officer1,
            'officer2' => $team->officer2,
            'officer3' => $team->officer3,
            'rescueRadio' => $team->rescueRadio,
        ])->values()->all();
    }

    protected function buildTablePages(Collection $officers, Collection $teams, Collection $users, Collection $equipment, Collection $boundaries): array
    {
        return [
            $this->paginateCollection($officers, 'personnel_page'),
            $this->paginateCollection($teams, 'teams_page'),
            $this->paginateCollection($users, 'users_page'),
            $this->paginateCollection($equipment, 'equipment_page'),
            $this->paginateCollection($boundaries, 'boundaries_page'),
        ];
    }

    protected function filterTableCollections(Request $request, Collection $officers, Collection $teams, Collection $users, Collection $equipment, Collection $boundaries): array
    {
        $search = mb_strtolower(mb_substr(trim((string) $request->query('resource_search', '')), 0, 100));
        $status = mb_strtolower(mb_substr(trim((string) $request->query('resource_status', '')), 0, 30));
        $role = mb_strtolower(mb_substr(trim((string) $request->query('user_role', '')), 0, 50));

        if ($search !== '') {
            $contains = fn (...$values) => str_contains(mb_strtolower(implode(' ', array_filter($values))), $search);
            $officers = $officers->filter(fn ($officer) => $contains(
                $officer->user?->first_name, $officer->user?->middle_name, $officer->user?->last_name,
                $officer->user?->account_code, $officer->rank, $officer->station?->station_name
            ));
            $teams = $teams->filter(fn ($team) => $contains(
                'team ' . $team->team_id,
                $team->teamLeader?->user?->first_name, $team->teamLeader?->user?->last_name,
                $team->commander?->user?->first_name, $team->commander?->user?->last_name
            ));
            $users = $users->filter(fn ($user) => $contains(
                $user->first_name, $user->middle_name, $user->last_name, $user->email,
                $user->account_code, $user->contact_number, $user->role?->role
            ));
            $equipment = $equipment->filter(fn ($item) => $contains($item->equipment_name, $item->type, $item->category, $item->model, $item->serial_number));
            $boundaries = $boundaries->filter(fn ($item) => $contains($item['barangay_name'] ?? null, $item['city_name'] ?? null, $item['boundary_status_label'] ?? null));
        }

        if ($status !== '') {
            $officers = $officers->filter(fn ($officer) => mb_strtolower((string) $officer->status) === $status);
            $teams = $teams->filter(fn ($team) => mb_strtolower((string) $team->status) === $status);
        }

        if ($role !== '') {
            $users = $users->filter(fn ($user) => mb_strtolower((string) $user->role?->role) === $role);
        }

        $sort = (string) $request->query('user_sort', 'name_asc');
        $users = match ($sort) {
            'name_desc' => $users->sortByDesc(fn ($user) => mb_strtolower(trim("{$user->last_name} {$user->first_name}"))),
            'role_asc' => $users->sortBy(fn ($user) => mb_strtolower((string) $user->role?->role)),
            'role_desc' => $users->sortByDesc(fn ($user) => mb_strtolower((string) $user->role?->role)),
            'date_desc' => $users->sortByDesc('updated_at'),
            'date_asc' => $users->sortBy('updated_at'),
            default => $users->sortBy(fn ($user) => mb_strtolower(trim("{$user->last_name} {$user->first_name}"))),
        };

        return [$officers->values(), $teams->values(), $users->values(), $equipment->values(), $boundaries->values()];
    }

    protected function paginateCollection(Collection $items, string $pageName, int $perPage = 10): LengthAwarePaginator
    {
        $page = max(1, LengthAwarePaginator::resolveCurrentPage($pageName));

        return (new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        ))->withQueryString();
    }

    protected function buildBoundaryBarangayList(?int $stationId, ?int $chiefCityId = null)
    {
        if ($this->isProvincialAdmin()) {
            $supportsBarangayBoundaries = Schema::hasColumn('boundary_location', 'barangay_id')
                && Schema::hasColumn('boundary_location', 'boundary_type');

            return Barangay::query()
                ->with($supportsBarangayBoundaries ? ['city', 'boundaryLocations'] : ['city'])
                ->orderBy('barangay_name')
                ->get()
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
                'data' => $assignment->load(['station', 'equipment']),
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
