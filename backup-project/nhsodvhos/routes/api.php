<?php

use App\Http\Controllers\NhsoApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [NhsoApiController::class, 'health']);

Route::prefix('auth')->group(function () {
    Route::post('/login', [NhsoApiController::class, 'login']);
    Route::get('/me', [NhsoApiController::class, 'me']);
    Route::post('/register', [NhsoApiController::class, 'register']);
});

Route::prefix('admin')->group(function () {
    Route::get('/users', [NhsoApiController::class, 'adminUsers']);
    Route::put('/users/{username}/status', [NhsoApiController::class, 'updateUserStatus']);
    Route::post('/users/{username}/reset-password', [NhsoApiController::class, 'resetUserPassword']);
    Route::post('/repair-fiscal-years', [NhsoApiController::class, 'repairFiscalYears']);
});

Route::prefix('user')->group(function () {
    Route::get('/preferences', [NhsoApiController::class, 'getPreferences']);
    Route::put('/preferences', [NhsoApiController::class, 'putPreferences']);
});

Route::prefix('dashboard')->group(function () {
    Route::get('/data', [NhsoApiController::class, 'dashboardData']);
    Route::post('/upload', [NhsoApiController::class, 'upload']);
    Route::post('/purge', [NhsoApiController::class, 'purge']);
    Route::post('/purge-selected', [NhsoApiController::class, 'purgeSelected']);
    Route::get('/upload-history', [NhsoApiController::class, 'uploadHistory']);
    Route::delete('/upload-history/{id}', [NhsoApiController::class, 'deleteUploadHistory']);
    Route::delete('/upload-history/file/{fileName}', [NhsoApiController::class, 'deleteUploadFile'])
        ->where('fileName', '.*');
    Route::post('/cleanup-old-data', [NhsoApiController::class, 'cleanupOldData']);
    Route::post('/log', [NhsoApiController::class, 'log']);
    Route::get('/log', [NhsoApiController::class, 'getLogs']);
    Route::post('/log/clear', [NhsoApiController::class, 'clearLogs']);
});
