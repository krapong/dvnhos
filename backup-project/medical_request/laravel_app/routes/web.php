<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('index');
});

// Move API-style routes here so they use the 'web' middleware (sessions)
Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth')->group(function() {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        
        Route::get('/requests', [RequestController::class, 'index']);
        Route::post('/requests', [RequestController::class, 'store']);
        Route::get('/dashboard-data', [RequestController::class, 'getDashboardData']);
        Route::post('/feedback', [RequestController::class, 'submitFeedback']);
        
        // Admin endpoints
        Route::post('/admin/update-status', [AdminController::class, 'updateRequestStatus']);
        Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
        Route::post('/admin/import-users', [AdminController::class, 'importUsers']);
        Route::post('/admin/reset-password', [AdminController::class, 'resetUserPassword']);
    });
});
