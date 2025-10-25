<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);



Route::middleware('auth:sanctum')->group(function () {
    
    // ================= Auth routes ================
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);


    // ================= Admin only routes ===============
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Welcome Admin']);
        });
        Route::get('/admin/users', function () {
            return response()->json(['message' => 'List all users']);
        });
        Route::get('/role', function () {
            return response()->json(['message' => 'List roles']);
        });
    });

    // ================= Member only routes ===============
    Route::middleware('role:member')->group(function () {
        Route::get('/member/dashboard', function () {
            return response()->json(['message' => 'Welcome Member']);
        });
        
        Route::get('/member/loans', function () {
            return response()->json(['message' => 'List member loans']);
        });
    });

    // ================= Routes accessible by both admin and member ===============
    Route::middleware('role:admin,member')->group(function () {
        // Shared routes here
        Route::get('/books', function () {
            return response()->json(['message' => 'List all books']);
        });
    });
});
