<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\FineController;

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
        Route::post('/loans', [LoanController::class, 'store']);
        Route::get('/loans/{loan}', [LoanController::class, 'show']);
        Route::put('/loans/{loan}', [LoanController::class, 'update']);
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy']);

        // Fines Management
        Route::get('/fines', [FineController::class, 'index']);
        Route::post('/fines', [FineController::class, 'store']);
        Route::get('/fines/{fine}', [FineController::class, 'show']);
        Route::put('/fines/{fine}', [FineController::class, 'update']);
        Route::delete('/fines/{fine}', [FineController::class, 'destroy']);
    });

    // ================= Member Only Routes ===============
    Route::middleware('role:member')->prefix('member')->group(function () {
        
        // Member's Loans
        Route::get('/loans', [LoanController::class, 'index']);
        Route::get('/loans/{loan}', [LoanController::class, 'show']);

        // Member's Fines
        Route::get('/fines', [FineController::class, 'index']);
        Route::get('/fines/{fine}', [FineController::class, 'show']);
    });

    // ================= Shared Routes (Admin & Member) ===============
    Route::middleware('role:admin,member')->group(function () {
        
        // Books (Read Only for Members)
        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{id}', [BookController::class, 'show']);

        // Authors (Read Only for Members)
        Route::get('/authors', [AuthorController::class, 'index']);
        Route::get('/authors/{id}', [AuthorController::class, 'show']);

        // Categories (Read Only for Members)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
    });
});
