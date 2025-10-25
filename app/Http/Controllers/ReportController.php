<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Fine;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get loan statistics summary
     */
    public function loanStatistics(Request $request)
    {
        $period = $request->input('period', 'month'); // day, week, month, year
        
        $dateFrom = match($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $totalLoans = Loan::where('loaned_at', '>=', $dateFrom)->count();
        $activeLoans = Loan::whereIn('status', ['borrowed', 'overdue'])
            ->where('loaned_at', '>=', $dateFrom)
            ->count();
        $returnedLoans = Loan::where('status', 'returned')
            ->where('loaned_at', '>=', $dateFrom)
            ->count();
        $overdueLoans = Loan::where('status', 'overdue')
            ->where('loaned_at', '>=', $dateFrom)
            ->count();

        return response()->json([
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'statistics' => [
                'total_loans' => $totalLoans,
                'active_loans' => $activeLoans,
                'returned_loans' => $returnedLoans,
                'overdue_loans' => $overdueLoans,
            ]
        ]);
    }

    /**
     * Get most borrowed books
     */
    public function mostBorrowedBooks(Request $request)
    {
        $limit = $request->input('limit', 10);
        $period = $request->input('period', 'all'); // all, month, year
        
        $query = Book::select('books.*', DB::raw('COUNT(loans.id) as loan_count'))
            ->leftJoin('loans', 'books.id', '=', 'loans.book_id')
            ->groupBy('books.id');

        if ($period === 'month') {
            $query->where('loans.loaned_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'year') {
            $query->where('loans.loaned_at', '>=', Carbon::now()->startOfYear());
        }

        $books = $query->with(['category', 'author'])
            ->orderBy('loan_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'period' => $period,
            'limit' => $limit,
            'books' => $books
        ]);
    }

    /**
     * Get most active members
     */
    public function mostActiveMembers(Request $request)
    {
        $limit = $request->input('limit', 10);
        $period = $request->input('period', 'all'); // all, month, year
        
        $query = Member::select('members.*', DB::raw('COUNT(loans.id) as loan_count'))
            ->leftJoin('loans', 'members.id', '=', 'loans.member_id')
            ->groupBy('members.id');

        if ($period === 'month') {
            $query->where('loans.loaned_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'year') {
            $query->where('loans.loaned_at', '>=', Carbon::now()->startOfYear());
        }

        $members = $query->with('user')
            ->orderBy('loan_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'period' => $period,
            'limit' => $limit,
            'members' => $members
        ]);
    }

    /**
     * Get overdue loans report
     */
    public function overdueLoans(Request $request)
    {
        $loans = Loan::where('status', 'overdue')
            ->with(['book', 'member.user', 'fine'])
            ->orderBy('due_at', 'asc')
            ->get();

        $totalOverdue = $loans->count();
        $totalFines = Fine::whereIn('loan_id', $loans->pluck('id'))
            ->where('status', 'unpaid')
            ->sum('amount');

        return response()->json([
            'total_overdue' => $totalOverdue,
            'total_unpaid_fines' => $totalFines,
            'loans' => $loans
        ]);
    }

    /**
     * Get fine collection report
     */
    public function fineReport(Request $request)
    {
        $period = $request->input('period', 'month'); // month, year, all
        
        $query = Fine::with(['loan.member.user', 'loan.book']);

        if ($period === 'month') {
            $query->where('created_at', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'year') {
            $query->where('created_at', '>=', Carbon::now()->startOfYear());
        }

        $fines = $query->get();
        
        $totalFines = $fines->sum('amount');
        $paidFines = $fines->where('status', 'paid')->sum('amount');
        $unpaidFines = $fines->where('status', 'unpaid')->sum('amount');

        return response()->json([
            'period' => $period,
            'summary' => [
                'total_fines' => $totalFines,
                'paid_fines' => $paidFines,
                'unpaid_fines' => $unpaidFines,
                'count_paid' => $fines->where('status', 'paid')->count(),
                'count_unpaid' => $fines->where('status', 'unpaid')->count(),
            ],
            'fines' => $fines
        ]);
    }

    /**
     * Get book inventory report
     */
    public function bookInventory(Request $request)
    {
        $categoryId = $request->input('category_id');
        
        $query = Book::with(['category', 'author']);
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $books = $query->get()->map(function ($book) {
            $activeLoanCount = $book->loans()
                ->whereIn('status', ['borrowed', 'overdue'])
                ->count();
            
            $book->active_loans = $activeLoanCount;
            $book->available_copies = max(0, $book->stock - $activeLoanCount);
            
            return $book;
        });

        $totalBooks = $books->sum('stock');
        $totalAvailable = $books->sum('available_copies');
        $totalBorrowed = $books->sum('active_loans');

        return response()->json([
            'summary' => [
                'total_books' => $totalBooks,
                'total_available' => $totalAvailable,
                'total_borrowed' => $totalBorrowed,
                'unique_titles' => $books->count(),
            ],
            'books' => $books
        ]);
    }

    /**
     * Get member statistics
     */
    public function memberStatistics(Request $request)
    {
        $totalMembers = Member::count();
        $activeMembers = Member::whereHas('loans', function ($query) {
            $query->whereIn('status', ['borrowed', 'overdue']);
        })->count();

        $newMembersThisMonth = Member::where('join_date', '>=', Carbon::now()->startOfMonth())
            ->count();

        $membersWithOverdue = Member::whereHas('loans', function ($query) {
            $query->where('status', 'overdue');
        })->count();

        $membersWithFines = Member::whereHas('loans.fine', function ($query) {
            $query->where('status', 'unpaid');
        })->count();

        return response()->json([
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'new_members_this_month' => $newMembersThisMonth,
            'members_with_overdue' => $membersWithOverdue,
            'members_with_fines' => $membersWithFines,
        ]);
    }

    /**
     * Get daily loan trend (for charts)
     */
    public function loanTrend(Request $request)
    {
        $days = $request->input('days', 30);
        
        $trend = Loan::select(
                DB::raw('DATE(loaned_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('loaned_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'days' => $days,
            'trend' => $trend
        ]);
    }

    /**
     * Get books by category distribution
     */
    public function booksByCategory(Request $request)
    {
        $distribution = Book::select('categories.name as category', DB::raw('COUNT(*) as count'))
            ->join('categories', 'books.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'distribution' => $distribution
        ]);
    }

    /**
     * Export comprehensive report
     */
    public function comprehensiveReport(Request $request)
    {
        $period = $request->input('period', 'month');
        
        $dateFrom = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        // Loan statistics
        $loanStats = [
            'total' => Loan::where('loaned_at', '>=', $dateFrom)->count(),
            'active' => Loan::whereIn('status', ['borrowed', 'overdue'])->count(),
            'returned' => Loan::where('status', 'returned')
                ->where('returned_at', '>=', $dateFrom)->count(),
            'overdue' => Loan::where('status', 'overdue')->count(),
        ];

        // Book statistics
        $bookStats = [
            'total_titles' => Book::count(),
            'total_copies' => Book::sum('stock'),
            'total_borrowed' => Loan::whereIn('status', ['borrowed', 'overdue'])->count(),
        ];

        // Member statistics
        $memberStats = [
            'total' => Member::count(),
            'active' => Member::whereHas('loans', function ($query) {
                $query->whereIn('status', ['borrowed', 'overdue']);
            })->count(),
            'new_this_period' => Member::where('join_date', '>=', $dateFrom)->count(),
        ];

        // Fine statistics
        $fineStats = [
            'total_amount' => Fine::where('created_at', '>=', $dateFrom)->sum('amount'),
            'paid_amount' => Fine::where('status', 'paid')
                ->where('created_at', '>=', $dateFrom)->sum('amount'),
            'unpaid_amount' => Fine::where('status', 'unpaid')
                ->where('created_at', '>=', $dateFrom)->sum('amount'),
        ];

        return response()->json([
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'report' => [
                'loans' => $loanStats,
                'books' => $bookStats,
                'members' => $memberStats,
                'fines' => $fineStats,
            ]
        ]);
    }
}
