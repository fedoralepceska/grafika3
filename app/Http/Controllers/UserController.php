<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return User::with('role')->get();
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'nullable|exists:user_roles,id'
        ]);

        $user->update([
            'role_id' => $validated['role_id']
        ]);

        return response()->json(['message' => 'Role updated successfully']);
    }
} 