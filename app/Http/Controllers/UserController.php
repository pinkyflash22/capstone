<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\FullAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role', 'address.barangay.city.province')
            ->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $addresses = FullAddress::with('barangay.city.province')->get();
        return view('admin.users.create', compact('roles', 'addresses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|string|unique:users,account_code|max:50',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,role_id',
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('role', 'address.barangay.city.province', 'shifts', 'incidentsReported');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $addresses = FullAddress::with('barangay.city.province')->get();
        return view('admin.users.edit', compact('user', 'roles', 'addresses'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'account_code')->ignore($user->user_id, 'user_id'),
            ],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,role_id',
            'address_id' => 'nullable|exists:full_addresses,address_id',
            'contact_number' => 'required|string|max:20',
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user->user_id, 'user_id'),
            ],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Check if user has related records
        if ($user->incidentsReported()->count() > 0 || 
            $user->posts()->count() > 0 ||
            $user->teamsAsCommander()->count() > 0) {
            return back()->with('error', 'Cannot delete user with existing related records.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}