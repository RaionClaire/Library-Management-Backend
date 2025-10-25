<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get loans near due date for the authenticated member
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoansNearDueDate(Request $request)
    {
        $days = $request->input('days', 3); // Default 3 days
        $user = Auth::user();

        // Get member profile
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        // Get loans near due date
        $loansNearDue = Loan::where('member_id', $member->id)
            ->nearDue($days)
            ->with(['book.author', 'book.category'])
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'book' => [
                        'id' => $loan->book->id,
                        'title' => $loan->book->title,
                        'author' => $loan->book->author->name,
                        'category' => $loan->book->category->name,
                    ],
                    'loaned_at' => $loan->loaned_at->format('Y-m-d'),
                    'due_at' => $loan->due_at->format('Y-m-d'),
                    'days_until_due' => $loan->daysUntilDue(),
                    'status' => $loan->status,
                ];
            });

        return response()->json([
            'message' => 'Loans near due date retrieved successfully',
            'count' => $loansNearDue->count(),
            'loans' => $loansNearDue,
        ]);
    }

    /**
     * Get overdue loans for the authenticated member
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOverdueLoans()
    {
        $user = Auth::user();

        // Get member profile
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        // Get overdue loans
        $overdueLoans = Loan::where('member_id', $member->id)
            ->overdue()
            ->with(['book.author', 'book.category', 'fine'])
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'book' => [
                        'id' => $loan->book->id,
                        'title' => $loan->book->title,
                        'author' => $loan->book->author->name,
                        'category' => $loan->book->category->name,
                    ],
                    'loaned_at' => $loan->loaned_at->format('Y-m-d'),
                    'due_at' => $loan->due_at->format('Y-m-d'),
                    'days_overdue' => abs($loan->daysUntilDue()),
                    'status' => $loan->status,
                    'fine' => $loan->fine ? [
                        'id' => $loan->fine->id,
                        'amount' => $loan->fine->amount,
                        'status' => $loan->fine->status,
                    ] : null,
                ];
            });

        return response()->json([
            'message' => 'Overdue loans retrieved successfully',
            'count' => $overdueLoans->count(),
            'loans' => $overdueLoans,
        ]);
    }

    /**
     * Get all notifications summary for the authenticated member
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNotificationsSummary(Request $request)
    {
        $days = $request->input('days', 3);
        $user = Auth::user();

        // Get member profile
        $member = Member::where('user_id', $user->id)->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member profile not found'
            ], 404);
        }

        // Count near due loans
        $nearDueCount = Loan::where('member_id', $member->id)
            ->nearDue($days)
            ->count();

        // Count overdue loans
        $overdueCount = Loan::where('member_id', $member->id)
            ->overdue()
            ->count();

        // Count active loans
        $activeLoansCount = Loan::where('member_id', $member->id)
            ->active()
            ->count();

        // Count unpaid fines
        $unpaidFinesCount = Loan::where('member_id', $member->id)
            ->whereHas('fine', function ($query) {
                $query->where('status', '!=', 'paid');
            })
            ->count();

        return response()->json([
            'message' => 'Notifications summary retrieved successfully',
            'summary' => [
                'active_loans' => $activeLoansCount,
                'near_due_loans' => $nearDueCount,
                'overdue_loans' => $overdueCount,
                'unpaid_fines' => $unpaidFinesCount,
            ],
            'has_notifications' => ($nearDueCount > 0 || $overdueCount > 0 || $unpaidFinesCount > 0),
        ]);
    }

    /**
     * Get all notifications for the authenticated member (Admin view)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMembersWithNearDueLoans(Request $request)
    {
        $days = $request->input('days', 3);

        // Get all loans near due date
        $loansNearDue = Loan::nearDue($days)
            ->with(['member.user', 'book.author', 'book.category'])
            ->get()
            ->groupBy('member_id')
            ->map(function ($loans, $memberId) {
                $member = $loans->first()->member;
                return [
                    'member' => [
                        'id' => $member->id,
                        'code' => $member->code,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'phone' => $member->phone,
                    ],
                    'loans_count' => $loans->count(),
                    'loans' => $loans->map(function ($loan) {
                        return [
                            'id' => $loan->id,
                            'book' => [
                                'id' => $loan->book->id,
                                'title' => $loan->book->title,
                                'author' => $loan->book->author->name,
                            ],
                            'due_at' => $loan->due_at->format('Y-m-d'),
                            'days_until_due' => $loan->daysUntilDue(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Members with loans near due date retrieved successfully',
            'total_members' => $loansNearDue->count(),
            'total_loans' => Loan::nearDue($days)->count(),
            'members' => $loansNearDue,
        ]);
    }

    /**
     * Get all members with overdue loans (Admin view)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllMembersWithOverdueLoans()
    {
        // Get all overdue loans
        $overdueLoans = Loan::overdue()
            ->with(['member.user', 'book.author', 'book.category', 'fine'])
            ->get()
            ->groupBy('member_id')
            ->map(function ($loans, $memberId) {
                $member = $loans->first()->member;
                return [
                    'member' => [
                        'id' => $member->id,
                        'code' => $member->code,
                        'name' => $member->user->name,
                        'email' => $member->user->email,
                        'phone' => $member->phone,
                    ],
                    'loans_count' => $loans->count(),
                    'total_fines' => $loans->sum(function ($loan) {
                        return $loan->fine ? $loan->fine->amount : 0;
                    }),
                    'loans' => $loans->map(function ($loan) {
                        return [
                            'id' => $loan->id,
                            'book' => [
                                'id' => $loan->book->id,
                                'title' => $loan->book->title,
                                'author' => $loan->book->author->name,
                            ],
                            'due_at' => $loan->due_at->format('Y-m-d'),
                            'days_overdue' => abs($loan->daysUntilDue()),
                            'fine' => $loan->fine ? [
                                'id' => $loan->fine->id,
                                'amount' => $loan->fine->amount,
                                'status' => $loan->fine->status,
                            ] : null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Members with overdue loans retrieved successfully',
            'total_members' => $overdueLoans->count(),
            'total_loans' => Loan::overdue()->count(),
            'members' => $overdueLoans,
        ]);
    }
}
