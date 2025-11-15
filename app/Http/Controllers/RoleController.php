<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {

        $data = Role::query()
            ->orderBy('role_id', 'desc')->get();

        return response()->json([
            'message' => 'Roles fetched successfully',
            'data' => $data,
        ]);
    }

    public function show(Role $role)
    {
        return response()->json([
            'role_id' => $role->role_id,
            'name' => $role->name,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Role created',
            'role_id' => $role->role_id,
        ], 201);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('roles', 'name')->ignore($role->role_id, 'role_id'),
            ],
        ]);

        $role->update(['name' => $validated['name']]);

        return response()->json(['message' => 'Role updated']);
    }

    public function destroy(Role $role)
    {
        // Prevent deletion if role has assigned users
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users',
            ], 409);
        }

        $role->delete();
        return response()->json(['message' => 'Role deleted']);
    }
}