<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    /**
     * Get all user roles
     */
    public function index()
    {
        $roles = UserRole::all();
        return response()->json($roles);
    }

    /**
     * Create a new role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles',
        ]);

        $role = UserRole::create([
            'name' => $validated['name'],
        ]);

        return response()->json($role, 201);
    }

    public function create()
    {
        return view('user-roles.create');
    }

    public function edit(UserRole $userRole)
    {
        return view('user-roles.edit', compact('userRole'));
    }

    public function update(Request $request, UserRole $userRole)
    {
        $validated = $request->validate([
            'name' => 'required|unique:user_roles,name,' . $userRole->id . '|max:255',
        ]);

        $userRole->update($validated);
        return response()->json($userRole);
    }

    public function destroy(UserRole $userRole)
    {
        $userRole->delete();
        return response()->json(null, 204);
    }
} 