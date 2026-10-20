<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DrugController;
use App\Http\Controllers\BoxController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DataTransferController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BoxRoundController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Require authentication for these routes
Route::middleware('auth')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::post('/alerts/{id}/resolve', [AlertController::class, 'resolve']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

    Route::apiResource('locations', LocationController::class);
    Route::apiResource('drugs', DrugController::class);
    Route::apiResource('boxes', BoxController::class);
    Route::get('/boxes/{boxId}/items', [BoxRoundController::class, 'currentItems']);
    Route::get('/boxes/{boxId}/rounds', [BoxRoundController::class, 'index']);
    Route::post('/boxes/{boxId}/rounds', [BoxRoundController::class, 'store']);
    Route::apiResource('alerts', AlertController::class);
    
    Route::patch('/users/{id}/approve', [UserController::class, 'approve']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::apiResource('users', UserController::class);

    Route::get('/reports/usage', [ReportController::class, 'usage']);
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/exports/{resource}.csv', [DataTransferController::class, 'export']);
    Route::get('/reports/export.pdf', [DataTransferController::class, 'reportPdf']);
    Route::post('/import/{resource}', [DataTransferController::class, 'import']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::patch('/settings', [SettingController::class, 'update']);
    Route::post('/settings/test-telegram', [SettingController::class, 'testTelegram']);
});
