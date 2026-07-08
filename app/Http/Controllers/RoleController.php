<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->paginate(15);
        return view('admin.users.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.users.roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|unique:roles,role|max:255',
        ]);

        Role::create($validated);
        Role::flushDefinitionCache();

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        $role->load('users');
        return view('admin.users.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        return view('admin.users.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'role')->ignore($role->role_id, 'role_id'),
            ],
        ]);

        $role->update($validated);
        Role::flushDefinitionCache();

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role with existing users.');
        }

        $role->delete();
        Role::flushDefinitionCache();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
