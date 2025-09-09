<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Get all users with order status
     */
    public function index()
    {
        $users = User::all()->map(function ($user) {
            $user->has_orders = $user->hasJobs();
            return $user;
        });
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

    /**
     * Change user password
     */
    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * Check if user has orders/invoices
     */
    public function checkUserOrders($id)
    {
        $user = User::findOrFail($id);
        $hasOrders = $user->hasJobs();
        
        return response()->json([
            'has_orders' => $hasOrders,
            'message' => $hasOrders ? 'User has associated orders/invoices' : 'User can be safely deleted'
        ]);
    }

    /**
     * Delete user account
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Check if user has any jobs/orders
        if ($user->hasJobs()) {
            return response()->json([
                'message' => 'Cannot delete user. User has associated jobs/orders.',
                'error' => 'has_jobs'
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
} 