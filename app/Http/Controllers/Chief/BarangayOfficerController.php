<?php

namespace App\Http\Controllers\Chief;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BarangayHall;
use App\Models\BarangayOfficer;
use App\Models\FullAddress;
use App\Models\Role;
use App\Models\User;
use App\Services\BoundaryManagementScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BarangayOfficerController extends Controller
{
    private const POSITIONS = [
        'captain' => 'Captain', 'kagawad' => 'Kagawad', 'secretary' => 'Secretary',
        'treasurer' => 'Treasurer', 'tanod' => 'Tanod', 'officer' => 'Officer',
    ];
    private const PROTECTED_ROLES = ['firefighter', 'admin', 'provincial admin', 'station admin', 'chief'];

    public function store(Request $request, BoundaryManagementScope $scope): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['existing', 'new'])],
            'user_id' => ['nullable', 'integer', 'exists:users,user_id', 'required_if:mode,existing'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,barangay_id'],
            'position' => ['required', Rule::in(array_keys(self::POSITIONS))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'first_name' => ['nullable', 'required_if:mode,new', 'string', 'max:100', 'regex:/^[\pL .\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^[\pL .\'-]+$/u'],
            'last_name' => ['nullable', 'required_if:mode,new', 'string', 'max:100', 'regex:/^[\pL .\'-]+$/u'],
            'email' => ['nullable', 'required_if:mode,new', 'email:rfc', 'max:255', 'unique:users,email'],
            'contact_number' => ['nullable', 'required_if:mode,new', 'regex:/^09\d{9}$/', 'unique:users,contact_number'],
            'street' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@*.!\-]+$/'],
        ]);

        $scope->stationForBarangay($scope->assertManager(Auth::user()), (int) $validated['barangay_id']);
        $this->assertCaptainAvailable((int) $validated['barangay_id'], $validated['position'], $validated['status']);
        $portalRole = $this->barangayPortalRole();

        [$officer, $temporaryPassword] = DB::transaction(function () use ($validated, $portalRole) {
            $temporaryPassword = null;
            if ($validated['mode'] === 'existing') {
                $user = User::query()->with(['role', 'barangayOfficer'])->lockForUpdate()->findOrFail($validated['user_id']);
                $roleName = strtolower(trim((string) $user->role?->role));
                if (in_array($roleName, self::PROTECTED_ROLES, true)) {
                    throw ValidationException::withMessages(['user_id' => 'Firefighters, administrators, and chiefs cannot be converted into barangay officers.']);
                }
                if ($user->barangayOfficer) {
                    throw ValidationException::withMessages(['user_id' => 'This user is already assigned as a barangay officer.']);
                }
                $previousRoleId = $user->role_id;
                $user->update(['role_id' => $portalRole->role_id]);
            } else {
                $address = FullAddress::create([
                    'street' => trim((string) ($validated['street'] ?? '')) ?: 'Barangay Hall',
                    'barangay_id' => $validated['barangay_id'],
                ]);
                $temporaryPassword = $validated['password'] ?? ('Pyro'.random_int(100000, 999999).'!');
                $user = User::create([
                    'account_code' => $this->generateAccountCode(),
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => filled($validated['middle_name'] ?? null) ? trim($validated['middle_name']) : null,
                    'last_name' => trim($validated['last_name']),
                    'email' => strtolower(trim($validated['email'])),
                    'contact_number' => $validated['contact_number'],
                    'role_id' => $portalRole->role_id,
                    'address_id' => $address->address_id,
                    'is_verified' => true,
                    'password' => Hash::make($temporaryPassword),
                ]);
                $previousRoleId = null;
            }

            $officer = BarangayOfficer::create([
                'barangay_id' => $validated['barangay_id'], 'user_id' => $user->user_id,
                'previous_role_id' => $previousRoleId, 'position' => self::POSITIONS[$validated['position']],
                'status' => $validated['status'], 'appointed_by' => Auth::id(),
            ]);
            $this->syncCaptain($officer);
            return [$officer->load(['user.role', 'barangay']), $temporaryPassword];
        });

        return response()->json(['success' => true, 'message' => 'Barangay officer added successfully.', 'officer' => $officer, 'temporary_password' => $validated['mode'] === 'new' && empty($validated['password']) ? $temporaryPassword : null], 201);
    }

    public function update(Request $request, BarangayOfficer $barangayOfficer, BoundaryManagementScope $scope): JsonResponse
    {
        $scope->stationForBarangay($scope->assertManager(Auth::user()), (int) $barangayOfficer->barangay_id);
        $validated = $request->validate([
            'position' => ['required', Rule::in(array_keys(self::POSITIONS))],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        $this->assertCaptainAvailable((int) $barangayOfficer->barangay_id, $validated['position'], $validated['status'], $barangayOfficer->barangay_officer_id);
        DB::transaction(function () use ($barangayOfficer, $validated) {
            $barangayOfficer->update(['position' => self::POSITIONS[$validated['position']], 'status' => $validated['status']]);
            $this->syncCaptain($barangayOfficer->fresh());
        });
        return response()->json(['success' => true, 'message' => 'Barangay officer updated.', 'officer' => $barangayOfficer->fresh()->load(['user.role', 'barangay'])]);
    }

    public function destroy(BarangayOfficer $barangayOfficer, BoundaryManagementScope $scope): JsonResponse
    {
        $scope->stationForBarangay($scope->assertManager(Auth::user()), (int) $barangayOfficer->barangay_id);
        DB::transaction(function () use ($barangayOfficer) {
            BarangayHall::query()->where('captain_user_id', $barangayOfficer->barangay_officer_id)->update(['captain_user_id' => null]);
            $user = $barangayOfficer->user;
            $previousRoleId = $barangayOfficer->previous_role_id;
            $barangayOfficer->delete();
            if ($user && $previousRoleId) $user->update(['role_id' => $previousRoleId]);
        });
        return response()->json(['success' => true, 'message' => 'Barangay officer assignment removed.']);
    }

    private function barangayPortalRole(): Role
    {
        $role = Role::query()->whereIn(DB::raw('LOWER(role)'), ['barangay', 'barangay officer', 'barangay captain'])->orderByRaw("CASE WHEN LOWER(role) = 'barangay' THEN 0 ELSE 1 END")->first();
        if (! $role) throw ValidationException::withMessages(['role' => 'No barangay portal role is configured. Add a Barangay role first.']);
        return $role;
    }

    private function assertCaptainAvailable(int $barangayId, string $position, string $status, ?int $ignoreId = null): void
    {
        if ($position !== 'captain' || $status !== 'active') return;
        $exists = BarangayOfficer::query()->where('barangay_id', $barangayId)->whereRaw('LOWER(position) = ?', ['captain'])->where('status', 'active')->when($ignoreId, fn ($query) => $query->where('barangay_officer_id', '!=', $ignoreId))->exists();
        if ($exists) throw ValidationException::withMessages(['position' => 'This barangay already has an active captain.']);
    }

    private function syncCaptain(BarangayOfficer $officer): void
    {
        $hall = BarangayHall::query()->where('barangay_id', $officer->barangay_id)->first();
        if (! $hall) return;
        if (strtolower($officer->position) === 'captain' && $officer->status === 'active') {
            $hall->update(['captain_user_id' => $officer->barangay_officer_id]);
        } elseif ((int) $hall->captain_user_id === (int) $officer->barangay_officer_id) {
            $hall->update(['captain_user_id' => null]);
        }
    }

    private function generateAccountCode(): string
    {
        do { $code = 'BRG-'.random_int(100000, 999999); } while (User::where('account_code', $code)->exists());
        return $code;
    }
}
