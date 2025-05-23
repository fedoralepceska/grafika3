<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Get all users
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'nullable|exists:user_roles,id',
        ]);

        $user = User::findOrFail($id);
        $user->role_id = $request->role_id;
        $user->save();

        return response()->json(['message' => 'User role updated successfully']);
    }
} 