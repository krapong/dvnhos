<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TelegramScheduleController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('app');
});
Route::post('/', function () {
    return redirect()->to(url('/'));
});

Route::prefix('auth')->group(function (): void {
    Route::get('/login', fn () => redirect()->to(url('/')));
    Route::get('/register', fn () => redirect()->to(url('/')));
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

    Route::middleware(['auth', 'role:admin'])->group(function (): void {
        Route::get('/roles', [AuthController::class, 'roles']);
        Route::get('/users', [AuthController::class, 'users']);
        Route::post('/users', [AuthController::class, 'storeUser']);
        Route::put('/users/{user}', [AuthController::class, 'updateUser']);
        Route::post('/users/{user}/approve', [AuthController::class, 'approveUser']);
        Route::post('/users/{user}/reject', [AuthController::class, 'rejectUser']);

        Route::get('/telegram/schedules', [TelegramScheduleController::class, 'index']);
        Route::put('/telegram/schedules', [TelegramScheduleController::class, 'update']);
        Route::post('/telegram/schedules/test', [TelegramScheduleController::class, 'test']);
        Route::put('/telegram/config', [TelegramScheduleController::class, 'updateConfig']);
    });
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
