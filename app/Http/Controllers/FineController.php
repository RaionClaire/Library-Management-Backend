<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // If admin, show all fines
        if ($user->hasRole('admin')) {
            $query = Fine::with(['loan.book', 'loan.member.user']);
            
            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            $fines = $query->orderBy('created_at', 'desc')->paginate(15);
        } 
        else {
            $member = $user->member;
            if (!$member) {
                return response()->json([
                    'message' => 'Member profile not found'
                ], 404);
            }
            
            $fines = Fine::with(['loan.book'])
                ->whereHas('loan', function ($query) use ($member) {
                    $query->where('member_id', $member->id);
                })
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        // Calculate totals
        $totalAmount = $fines->sum('amount');
        $paidAmount = $fines->where('status', 'paid')->sum('amount');
        $unpaidAmount = $fines->where('status', 'unpaid')->sum('amount');

        return response()->json([
            'fines' => $fines,
            'summary' => [
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'unpaid_amount' => $unpaidAmount,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'sometimes|integer|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $loan = Loan::findOrFail($request->loan_id);

        // Check if fine already exists
        if ($loan->fine) {
            return response()->json([
                'message' => 'Fine already exists for this loan.'
            ], 400);
        }

        // Calculate amount if not provided
        $amount = $request->amount;
        if (!$amount) {
            if ($loan->returned_at && $loan->returned_at->isAfter($loan->due_at)) {
                $daysLate = $loan->returned_at->diffInDays($loan->due_at);
                $amount = $daysLate * 2000; // 2000 rupiah per day
            } else {
                return response()->json([
                    'message' => 'Cannot calculate fine. Book not overdue or not returned yet.'
                ], 400);
            }
        }

        $fine = Fine::create([
            'loan_id' => $request->loan_id,
            'amount' => $amount,
            'status' => 'unpaid',
            'note' => $request->note ?? "Fine for overdue book",
        ]);

        return response()->json([
            'message' => 'Fine created successfully',
            'fine' => $fine->load(['loan.book', 'loan.member.user'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Fine $fine)
    {
        $user = Auth::user();
        
        // If member, check if they own this fine
        if ($user->hasRole('member')) {
            $member = $user->member;
            if (!$member || $fine->loan->member_id !== $member->id) {
                return response()->json([
                    'message' => 'Unauthorized to view this fine.'
                ], 403);
            }
        }

        return response()->json([
            'fine' => $fine->load(['loan.book', 'loan.member.user'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fine $fine)
    {
        $request->validate([
            'amount' => 'sometimes|integer|min:0',
            'status' => 'sometimes|in:paid,unpaid',
            'note' => 'sometimes|nullable|string|max:255',
        ]);

        if ($request->has('amount')) {
            $fine->amount = $request->amount;
        }

        if ($request->has('status')) {
            $fine->status = $request->status;
        }

        if ($request->has('note')) {
            $fine->note = $request->note;
        }

        $fine->save();

        return response()->json([
            'message' => 'Fine updated successfully',
            'fine' => $fine->load(['loan.book', 'loan.member.user'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fine $fine)
    {
        // Only allow deletion if fine is paid
        if ($fine->status === 'unpaid') {
            return response()->json([
                'message' => 'Cannot delete unpaid fine.'
            ], 400);
        }

        $fine->delete();

        return response()->json([
            'message' => 'Fine deleted successfully'
        ]);
    }

    /**
     * Pay a fine
     */
    public function payFine(Request $request, Fine $fine)
    {
        if ($fine->status === 'paid') {
            return response()->json([
                'message' => 'Fine has already been paid.'
            ], 400);
        }

        $fine->status = 'paid';
        $fine->note = ($fine->note ?? '') . " | Paid on " . Carbon::now()->format('Y-m-d H:i:s');
        $fine->save();

        return response()->json([
            'message' => 'Fine paid successfully',
            'fine' => $fine->load(['loan.book', 'loan.member.user'])
        ]);
    }

    /**
     * Calculate fine for a loan
     */
    public function calculateFine(Request $request, $loanId)
    {
        $loan = Loan::findOrFail($loanId);

        if (!$loan->returned_at) {
            // If not returned yet, calculate based on current date
            $now = Carbon::now();
            if ($now->isAfter($loan->due_at)) {
                $daysLate = $now->diffInDays($loan->due_at);
                $amount = $daysLate * 2000;

                return response()->json([
                    'loan_id' => $loan->id,
                    'due_at' => $loan->due_at,
                    'current_date' => $now->toDateString(),
                    'days_late' => $daysLate,
                    'fine_amount' => $amount,
                    'fine_per_day' => 2000,
                    'status' => 'Not returned yet'
                ]);
            } else {
                return response()->json([
                    'loan_id' => $loan->id,
                    'due_at' => $loan->due_at,
                    'current_date' => $now->toDateString(),
                    'days_late' => 0,
                    'fine_amount' => 0,
                    'fine_per_day' => 2000,
                    'status' => 'Not overdue'
                ]);
            }
        } else {
            // Calculate based on return date
            if ($loan->returned_at->isAfter($loan->due_at)) {
                $daysLate = $loan->returned_at->diffInDays($loan->due_at);
                $amount = $daysLate * 2000;

                return response()->json([
                    'loan_id' => $loan->id,
                    'due_at' => $loan->due_at,
                    'returned_at' => $loan->returned_at->toDateString(),
                    'days_late' => $daysLate,
                    'fine_amount' => $amount,
                    'fine_per_day' => 2000,
                    'status' => 'Returned late',
                    'existing_fine' => $loan->fine
                ]);
            } else {
                return response()->json([
                    'loan_id' => $loan->id,
                    'due_at' => $loan->due_at,
                    'returned_at' => $loan->returned_at->toDateString(),
                    'days_late' => 0,
                    'fine_amount' => 0,
                    'fine_per_day' => 2000,
                    'status' => 'Returned on time',
                    'existing_fine' => $loan->fine
                ]);
            }
        }
    }

    /**
     * Get unpaid fines summary
     */
    public function getUnpaidFines(Request $request)
    {
        $user = Auth::user();
        
        if ($user->hasRole('admin')) {
            $fines = Fine::where('status', 'unpaid')
                ->with(['loan.book', 'loan.member.user'])
                ->get();
        } else {
            $member = $user->member;
            if (!$member) {
                return response()->json([
                    'message' => 'Member profile not found'
                ], 404);
            }
            
            $fines = Fine::where('status', 'unpaid')
                ->whereHas('loan', function ($query) use ($member) {
                    $query->where('member_id', $member->id);
                })
                ->with(['loan.book'])
                ->get();
        }

        $totalUnpaid = $fines->sum('amount');

        return response()->json([
            'unpaid_fines' => $fines,
            'total_unpaid' => $totalUnpaid,
            'count' => $fines->count()
        ]);
    }
}
