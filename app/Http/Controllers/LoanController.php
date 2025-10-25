<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Book;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LoanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $user->load('role');
        
        // If admin, show all loans
        if ($user->hasRole('admin')) {
            $loans = Loan::with(['book.author', 'book.category', 'member.user', 'fine'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } 
        // If member, show only their loans
        else {
            $member = $user->member;
            if (!$member) {
                return response()->json([
                    'message' => 'Member profile not found'
                ], 404);
            }
            
            $loans = Loan::with(['book.author', 'book.category', 'fine'])
                ->where('member_id', $member->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return response()->json($loans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'book_id' => 'required|exists:books,id',
        ]);

        // Check if book is available
        $book = Book::findOrFail($request->book_id);
        if ($book->availableCopies() <= 0) {
            return response()->json([
                'message' => 'Book is not available. All copies are currently loaned.'
            ], 400);
        }

        // Check if member already has this book on loan
        $existingLoan = Loan::where('member_id', $request->member_id)
            ->where('book_id', $request->book_id)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->first();

        if ($existingLoan) {
            return response()->json([
                'message' => 'Member already has this book on loan.'
            ], 400);
        }

        // Create loan with automatic due date (+7 days)
        $loanedAt = Carbon::now();
        $dueAt = $loanedAt->copy()->addDays(7);

        $loan = Loan::create([
            'member_id' => $request->member_id,
            'book_id' => $request->book_id,
            'loaned_at' => $loanedAt,
            'due_at' => $dueAt,
            'status' => 'borrowed',
        ]);

        return response()->json([
            'message' => 'Loan created successfully',
            'loan' => $loan->load(['book.author', 'book.category', 'member.user'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Loan $loan)
    {
        $user = Auth::user();
        
        // If member, check if they own this loan
        if ($user->hasRole('member')) {
            $member = $user->member;
            if (!$member || $loan->member_id !== $member->id) {
                return response()->json([
                    'message' => 'Unauthorized to view this loan.'
                ], 403);
            }
        }

        return response()->json([
            'loan' => $loan->load(['book.author', 'book.category', 'member.user', 'fine'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'status' => 'sometimes|required|in:borrowed,returned,overdue',
            'returned_at' => 'sometimes|nullable|date',
        ]);

        // If marking as returned
        if ($request->status === 'returned' || $request->has('returned_at')) {
            $returnedAt = $request->returned_at ? Carbon::parse($request->returned_at) : Carbon::now();
            $loan->returned_at = $returnedAt;
            $loan->status = 'returned';

            // Calculate fine if overdue
            if ($returnedAt->isAfter($loan->due_at)) {
                $daysLate = $returnedAt->diffInDays($loan->due_at);
                $fineAmount = $daysLate * 2000; // 2000 rupiah per day

                // Create or update fine
                $loan->fine()->updateOrCreate(
                    ['loan_id' => $loan->id],
                    [
                        'amount' => $fineAmount,
                        'status' => 'unpaid',
                        'note' => "Late return by {$daysLate} day(s)"
                    ]
                );
            }
        } else {
            $loan->status = $request->status;
        }

        $loan->save();

        return response()->json([
            'message' => 'Loan updated successfully',
            'loan' => $loan->load(['book.author', 'book.category', 'member.user', 'fine'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loan $loan)
    {
        // Only allow deletion if loan is returned and no unpaid fines
        if ($loan->status !== 'returned') {
            return response()->json([
                'message' => 'Cannot delete active loan. Please return the book first.'
            ], 400);
        }

        if ($loan->fine && $loan->fine->status === 'unpaid') {
            return response()->json([
                'message' => 'Cannot delete loan with unpaid fines.'
            ], 400);
        }

        $loan->delete();

        return response()->json([
            'message' => 'Loan deleted successfully'
        ]);
    }

    /**
     * Return a book
     */
    public function returnBook(Request $request, Loan $loan)
    {
        if ($loan->status === 'returned') {
            return response()->json([
                'message' => 'Book has already been returned.'
            ], 400);
        }

        $returnedAt = Carbon::now();
        $loan->returned_at = $returnedAt;
        $loan->status = 'returned';

        // Calculate fine if overdue
        if ($returnedAt->isAfter($loan->due_at)) {
            $daysLate = $returnedAt->diffInDays($loan->due_at);
            $fineAmount = $daysLate * 2000; // 2000 rupiah per day

            // Create fine
            $fine = $loan->fine()->updateOrCreate(
                ['loan_id' => $loan->id],
                [
                    'amount' => $fineAmount,
                    'status' => 'unpaid',
                    'note' => "Late return by {$daysLate} day(s). Fine: Rp " . number_format($fineAmount, 0, ',', '.')
                ]
            );

            $loan->save();

            return response()->json([
                'message' => 'Book returned with fine',
                'loan' => $loan->load(['book', 'member.user', 'fine']),
                'fine' => $fine,
                'days_late' => $daysLate
            ]);
        }

        $loan->save();

        return response()->json([
            'message' => 'Book returned successfully',
            'loan' => $loan->load(['book', 'member.user'])
        ]);
    }

    /**
     * Extend loan due date (admin only)
     */
    public function extendDueDate(Request $request, Loan $loan)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30'
        ]);

        if ($loan->status !== 'borrowed') {
            return response()->json([
                'message' => 'Can only extend active loans.'
            ], 400);
        }

        $loan->due_at = Carbon::parse($loan->due_at)->addDays($request->days);
        
        // If was overdue, change back to borrowed
        if ($loan->status === 'overdue') {
            $loan->status = 'borrowed';
        }
        
        $loan->save();

        return response()->json([
            'message' => "Loan extended by {$request->days} day(s)",
            'loan' => $loan->load(['book', 'member.user'])
        ]);
    }
}
