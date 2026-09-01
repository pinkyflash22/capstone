<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\StationOfficer;
use App\Models\User;
use App\Models\FullAddress;
use App\Models\Role;
use App\Support\UserActivityLogger;

class StationOfficerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        $validated = $request->validate([
            'station_id'=>'required|exists:stations,station_id',
            'user_id'=>'required|exists:users,user_id|unique:station_officers,user_id',
            'rank'=> 'required|string|max:50',
        ]);
        $this->assertStationAccess((int) $validated['station_id']);
        $this->assertAssignableUser((int) $validated['user_id']);

      $data = StationOfficer::create($validated);
        UserActivityLogger::log('assigned personnel', Auth::user()?->user_id, $request);
        return response()->json(['success'=>true, 'data'=>$data]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:available,dispatched,on-scene,busy,off-duty,on-leave,unavailable,training,resigned',
        ]);

        $stationOfficer = StationOfficer::find($id);
        if (!$stationOfficer) {
            return response()->json(['success' => false, 'message' => 'Station Officer not found'], 404);
        }
        $this->assertStationAccess((int) $stationOfficer->station_id);

        $stationOfficer->status = $validated['status'];
        $stationOfficer->save();
        UserActivityLogger::log('updated pers status', Auth::user()?->user_id, $request);

        return response()->json(['success' => true, 'message' => 'Station Officer status updated successfully']);
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
public function updateUser(Request $request, string $id)
{
    $user = User::find($id);
    
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }
    $this->assertUserAccess($user);
    
    $rules = [
        'first_name' => 'sometimes|required|string|max:255',
        'middle_name' => 'sometimes|nullable|string|max:255',
        'last_name' => 'sometimes|required|string|max:255',
        'contact_number' => 'sometimes|required|string|max:20',
        'password' => 'sometimes|nullable|string|min:8',
        'role_id' => 'sometimes|nullable|exists:roles,role_id',
        'email_verified_at' => 'sometimes|nullable|date',
        'barangay_id' => 'sometimes|nullable|exists:barangays,barangay_id',
        'street' => 'sometimes|nullable|string|max:255',
    ];
    
    // Only validate email uniqueness if it's being changed
    if ($request->has('email') && $request->email !== $user->email) {
        $rules['email'] = 'required|email|unique:users,email,' . $user->user_id . ',user_id';
    } elseif ($request->has('email')) {
        $rules['email'] = 'required|email';
    }
    
    $validated = $request->validate($rules);

    if (isset($validated['role_id']) && ! $this->isProvincialAdmin()) {
        $requestedRole = Role::roleNameForId((int) $validated['role_id']);
        abort_if(in_array($requestedRole, ['admin', 'provincial admin'], true), 403, 'You cannot assign a province-wide role.');
    }
    
    // Update fields only if they exist in validated data
    if (isset($validated['first_name'])) $user->first_name = $validated['first_name'];
    if (isset($validated['middle_name'])) $user->middle_name = $validated['middle_name'];
    if (isset($validated['last_name'])) $user->last_name = $validated['last_name'];
    if (isset($validated['contact_number'])) $user->contact_number = $validated['contact_number'];
    if (isset($validated['email'])) $user->email = $validated['email'];
    if (isset($validated['role_id'])) $user->role_id = $validated['role_id'];
    if (array_key_exists('email_verified_at', $validated)) $user->email_verified_at = $validated['email_verified_at'];

    if (!empty($validated['password'])) {
        $user->password = Hash::make($validated['password']);
    }

    if (array_key_exists('barangay_id', $validated) || array_key_exists('street', $validated)) {
        $barangayId = $validated['barangay_id'] ?? null;
        $street = $validated['street'] ?? null;

        if ($barangayId || $street) {
            if ($user->address_id) {
                $address = FullAddress::find($user->address_id);
                if ($address) {
                    if (array_key_exists('street', $validated)) {
                        $address->street = $street;
                    }
                    if (array_key_exists('barangay_id', $validated) && $barangayId) {
                        $address->barangay_id = $barangayId;
                    }
                    $address->save();
                } else {
                    $address = FullAddress::create([
                        'street' => $street,
                        'barangay_id' => $barangayId,
                    ]);
                    $user->address_id = $address->address_id;
                }
            } else {
                $address = FullAddress::create([
                    'street' => $street,
                    'barangay_id' => $barangayId,
                ]);
                $user->address_id = $address->address_id;
            }
        }
    }
    
    $user->save();
    UserActivityLogger::log('updated user', Auth::user()?->user_id, $request);

    return response()->json(['success' => true, 'message' => 'User updated successfully']);
}

public function updateStationOfficer(Request $request, string $id)
{
    $validated = $request->validate([
        'rank' => 'sometimes|required|string|max:50',
        'status' => 'required|in:available,dispatched,on-scene,busy,off-duty,on-leave,unavailable,training,resigned',
        'station_id' => 'sometimes|required|exists:stations,station_id',
    ]);     

    $stationOfficer = StationOfficer::find($id);
    if (!$stationOfficer) {
        return response()->json(['success' => false, 'message' => 'Station Officer not found'], 404);
    }
    $this->assertStationAccess((int) $stationOfficer->station_id);
    if (isset($validated['station_id'])) {
        $this->assertStationAccess((int) $validated['station_id']);
    }
    if (isset($validated['rank'])) $stationOfficer->rank = $validated['rank'];
    if (isset($validated['status'])) $stationOfficer->status = $validated['status'];
    if (isset($validated['station_id'])) $stationOfficer->station_id = $validated['station_id'];
    $stationOfficer->save();
    UserActivityLogger::log('updated personnel', Auth::user()?->user_id, $request);
    return response()->json(['success' => true, 'message' => 'Station Officer updated successfully']);

}

public function destroyUser(string $id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }
    $this->assertUserAccess($user);

    if ((int) Auth::id() === (int) $user->user_id) {
        return response()->json(['success' => false, 'message' => 'You cannot delete your own account'], 422);
    }

    if (
        $user->incidentsReported()->count() > 0 ||
        $user->posts()->count() > 0 ||
        $user->comments()->count() > 0 ||
        $user->likes()->count() > 0 ||
        $user->teamsAsCommander()->count() > 0 ||
        $user->teamsAsLeader()->count() > 0 ||
        $user->teamsAsPumper()->count() > 0 ||
        $user->teamsAsOfficer1()->count() > 0 ||
        $user->teamsAsOfficer2()->count() > 0 ||
        $user->teamsAsOfficer3()->count() > 0 ||
        $user->teamsAsRescueRadio()->count() > 0
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Cannot delete user with existing related records.',
        ], 422);
    }

    DB::beginTransaction();

    try {
        StationOfficer::where('user_id', $user->user_id)->delete();
        $user->delete();

        DB::commit();
        UserActivityLogger::log('deleted user', Auth::user()?->user_id, request());

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete user.',
        ], 500);
    }
}
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stationOfficer = StationOfficer::find($id);
        if (!$stationOfficer) {
            return response()->json(['success' => false, 'message' => 'Station Officer not
    found'], 404);
        }
        $this->assertStationAccess((int) $stationOfficer->station_id);
        $stationOfficer->delete();
        UserActivityLogger::log('removed personnel', Auth::user()?->user_id, request());
        return response()->json(['success' => true, 'message' => 'Station Officer removed successfully']);
    }

    private function isProvincialAdmin(): bool
    {
        return Role::roleNameForId((int) (Auth::user()?->role_id ?? 0)) === 'provincial admin';
    }

    private function managedStationId(): ?int
    {
        $stationId = StationOfficer::query()
            ->where('user_id', Auth::user()?->user_id)
            ->value('station_id');

        return $stationId ? (int) $stationId : null;
    }

    private function assertStationAccess(int $stationId): void
    {
        if ($this->isProvincialAdmin()) {
            return;
        }

        $managedStationId = $this->managedStationId();
        abort_unless($managedStationId && $managedStationId === $stationId, 403, 'You can only manage personnel assigned to your station.');
    }

    private function assertUserAccess(User $user): void
    {
        if ($this->isProvincialAdmin()) {
            return;
        }

        $targetStationId = StationOfficer::query()
            ->where('user_id', $user->user_id)
            ->value('station_id');

        abort_unless($targetStationId, 403, 'This user is not assigned to your station.');
        $this->assertStationAccess((int) $targetStationId);
    }

    private function assertAssignableUser(int $userId): void
    {
        if ($this->isProvincialAdmin()) {
            return;
        }

        $role = Role::roleNameForId((int) User::query()->whereKey($userId)->value('role_id'));
        abort_if(in_array($role, ['admin', 'provincial admin'], true), 403, 'You cannot assign a province-wide account to a station.');
    }
}
