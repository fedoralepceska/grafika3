<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function index()
    {
        $roles = UserRole::all();
        return response()->json($roles);
    }

    public function create()
    {
        return view('user-roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:user_roles,name|max:255',
        ]);

        $role = UserRole::create($validated);
        return response()->json($role, 201);
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