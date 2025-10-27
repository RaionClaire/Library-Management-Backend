<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MemberController extends Controller
{
    /**
     * Get authenticated member's profile
     */
    public function profile()
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        return response()->json([
            'member' => $member->load('user')
        ]);
    }

    /**
     * Update authenticated member's profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        $member->update([
            'phone' => $request->phone ?? $member->phone,
            'address' => $request->address ?? $member->address,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'member' => $member->load('user')
        ]);
    }

    /**
     * Get member's loan statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        $totalLoans = $member->loans()->count();
        $activeLoans = $member->loans()->whereIn('status', ['borrowed', 'overdue'])->count();
        $returnedLoans = $member->loans()->where('status', 'returned')->count();
        $overdueLoans = $member->loans()->where('status', 'overdue')->count();
        $totalFines = $member->loans()
            ->whereHas('fine', function ($query) {
                $query->where('status', 'unpaid');
            })
            ->with('fine')
            ->get()
            ->sum(function ($loan) {
                return $loan->fine ? $loan->fine->amount : 0;
            });

        return response()->json([
            'statistics' => [
                'total_loans' => $totalLoans,
                'active_loans' => $activeLoans,
                'returned_loans' => $returnedLoans,
                'overdue_loans' => $overdueLoans,
                'unpaid_fines' => $totalFines,
            ]
        ]);
    }
}
