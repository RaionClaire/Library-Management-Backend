<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\FineController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - Public
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| API Routes - Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // ================= Auth Routes ================
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // ================= Admin Only Routes ===============
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        
        // Users Management
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/statistics', [UserController::class, 'statistics']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::patch('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);

        // Authors Management
        Route::get('/authors', [AuthorController::class, 'index']);
        Route::post('/authors', [AuthorController::class, 'store']);
        Route::get('/authors/{id}', [AuthorController::class, 'show']);
        Route::put('/authors/{id}', [AuthorController::class, 'update']);
        Route::delete('/authors/{id}', [AuthorController::class, 'destroy']);

        // Categories Management
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Books Management
        Route::get('/books', [BookController::class, 'index']);
        Route::post('/books', [BookController::class, 'store']);
        Route::get('/books/{id}', [BookController::class, 'show']);
        Route::put('/books/{id}', [BookController::class, 'update']);
        Route::delete('/books/{id}', [BookController::class, 'destroy']);

        // Loans Management
        Route::get('/loans', [LoanController::class, 'index']);
        Route::post('/loans', [LoanController::class, 'store']); // Direct loan creation
        Route::get('/loans/{loan}', [LoanController::class, 'show']);
        Route::put('/loans/{loan}', [LoanController::class, 'update']);
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy']);
        Route::post('/loans/{loan}/return', [LoanController::class, 'returnBook']);
        Route::post('/loans/{loan}/extend', [LoanController::class, 'extendDueDate']);
        
        // Loan Approval System
        Route::get('/loans/pending/all', [LoanController::class, 'getPendingLoans']);
        Route::post('/loans/{loan}/approve', [LoanController::class, 'approveLoan']);
        Route::post('/loans/{loan}/reject', [LoanController::class, 'rejectLoan']);

        // Fines Management
        Route::get('/fines', [FineController::class, 'index']);
        Route::post('/fines', [FineController::class, 'store']);
        Route::get('/fines/{fine}', [FineController::class, 'show']);
        Route::put('/fines/{fine}', [FineController::class, 'update']);
        Route::delete('/fines/{fine}', [FineController::class, 'destroy']);
        Route::post('/fines/{fine}/pay', [FineController::class, 'payFine']);
        Route::get('/fines/unpaid/summary', [FineController::class, 'getUnpaidFines']);
        Route::get('/fines/calculate/{loanId}', [FineController::class, 'calculateFine']);

        // Notifications for Admin (view all members)
        Route::get('/notifications/near-due', [NotificationController::class, 'getAllMembersWithNearDueLoans']);
        Route::get('/notifications/overdue', [NotificationController::class, 'getAllMembersWithOverdueLoans']);

        // Reports (Admin Only)
        Route::prefix('reports')->group(function () {
            Route::get('/loans/statistics', [ReportController::class, 'loanStatistics']);
            Route::get('/loans/trend', [ReportController::class, 'loanTrend']);
            Route::get('/books/most-borrowed', [ReportController::class, 'mostBorrowedBooks']);
            Route::get('/books/inventory', [ReportController::class, 'bookInventory']);
            Route::get('/books/by-category', [ReportController::class, 'booksByCategory']);
            Route::get('/members/most-active', [ReportController::class, 'mostActiveMembers']);
            Route::get('/members/statistics', [ReportController::class, 'memberStatistics']);
            Route::get('/loans/overdue', [ReportController::class, 'overdueLoans']);
            Route::get('/fines', [ReportController::class, 'fineReport']);
            Route::get('/comprehensive', [ReportController::class, 'comprehensiveReport']);
        });
    });

    // ================= Member Only Routes ===============
    Route::middleware('role:member')->prefix('member')->group(function () {
        
        // Member's Profile
        Route::get('/profile', [MemberController::class, 'profile']);
        Route::put('/profile', [MemberController::class, 'updateProfile']);
        Route::get('/statistics', [MemberController::class, 'statistics']);

        // Member's Loans
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/{loan}', [LoanController::class, 'show']);
        Route::post('/loans', [LoanController::class, 'proposeLoan']);

        // Member's Fines
        Route::get('/fines', [FineController::class, 'index']);
        Route::get('/fines/{fine}', [FineController::class, 'show']);
        Route::get('/fines/unpaid/summary', [FineController::class, 'getUnpaidFines']);
        Route::post('/fines/{fine}/pay', [FineController::class, 'payFine']);

        // Member's Notifications
        Route::get('/notifications/summary', [NotificationController::class, 'getNotificationsSummary']);
        Route::get('/notifications/near-due', [NotificationController::class, 'getLoansNearDueDate']);
        Route::get('/notifications/overdue', [NotificationController::class, 'getOverdueLoans']);
    });

    // ================= Shared Routes (Admin & Member) ===============
    Route::middleware('role:admin,member')->group(function () {
        
        // Books (Read Only for Members)
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{id}', [BookController::class, 'show']);
        Route::get('/books-total', [BookController::class, 'totalBooks']);
        Route::get('/books/search', [BookController::class, 'search']);
        Route::get('/books/filter/category', [BookController::class, 'filterByCategory']);
        Route::get('/books/filter/author', [BookController::class, 'filterByAuthor']);

        // Authors (Read Only for Members)
        Route::get('/authors', [AuthorController::class, 'index']);
        Route::get('/authors/{id}', [AuthorController::class, 'show']);
        Route::get('/authors/{id}/books', [AuthorController::class, 'getBooksByAuthor']);

        // Categories (Read Only for Members)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::get('/categories/{id}/books', [CategoryController::class, 'getBooksByCategory']);
    });
});
