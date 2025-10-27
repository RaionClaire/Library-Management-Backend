<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        // Build query with role relationship
        $query = User::with('role');

        // Filter by role if provided
        if ($request->has('role')) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort by field
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Check if pagination is needed
        if ($request->boolean('all')) {
            // Return all users without pagination
            $users = $query->get();
            return response()->json([
                'data' => $users,
                'total' => $users->count()
            ]);
        }

        // Return paginated results (default)
        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['role', 'member'])->findOrFail($id);

        return response()->json([
            'data' => $user
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id',
            'status' => 'sometimes|required|boolean',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->has('role_id')) {
            $user->role_id = $request->role_id;
        }

        if ($request->has('status')) {
            $user->status = $request->status;
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user->load('role')
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();

        // Prevent self-deletion
        if ($currentUser->id == $id) {
            return response()->json([
                'message' => 'You cannot delete your own account.'
            ], 400);
        }

        $user = User::findOrFail($id);

        // Check if user has active loans (if has member profile)
        if ($user->member) {
            $activeLoans = $user->member->loans()
                ->whereIn('status', ['borrowed', 'overdue'])
                ->count();

            if ($activeLoans > 0) {
                return response()->json([
                    'message' => 'Cannot delete user with active loans.'
                ], 400);
            }

            $unpaidFines = $user->member->loans()
                ->whereHas('fine', function ($query) {
                    $query->where('status', 'unpaid');
                })
                ->count();

            if ($unpaidFines > 0) {
                return response()->json([
                    'message' => 'Cannot delete user with unpaid fines.'
                ], 400);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        $currentUser = Auth::user();
        if ($currentUser->id == $id) {
            return response()->json([
                'message' => 'You cannot change your own status.'
            ], 400);
        }

        $user->status = !$user->status;
        $user->save();

        return response()->json([
            'message' => 'User status updated successfully',
            'data' => $user->load('role')
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', true)->count();
        $inactiveUsers = User::where('status', false)->count();
        
        $adminCount = User::whereHas('role', function ($query) {
            $query->where('name', 'admin');
        })->count();
        
        $memberCount = User::whereHas('role', function ($query) {
            $query->where('name', 'member');
        })->count();

        return response()->json([
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'inactive_users' => $inactiveUsers,
            'admins' => $adminCount,
            'members' => $memberCount,
        ]);
    }
}
