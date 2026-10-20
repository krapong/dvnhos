<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DietTemplateController;
use App\Http\Controllers\Api\MealOrderController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('dashboard/{scope}', [DashboardController::class, 'show']);
    Route::get('patients', [PatientController::class, 'index']);
    Route::get('patients/{patient:hn}', [PatientController::class, 'show']);
    Route::post('patients/{patient:hn}/transfer', [PatientController::class, 'transfer']);
    Route::post('patients/{patient:hn}/discharge', [PatientController::class, 'discharge']);

    Route::get('diet-templates', [DietTemplateController::class, 'index']);
    Route::get('diet-types', [DietTemplateController::class, 'types']);
    Route::post('diet-templates', [DietTemplateController::class, 'store']);
    Route::put('diet-templates/{dietTemplate}', [DietTemplateController::class, 'update']);

    Route::get('meal-orders', [MealOrderController::class, 'index']);
    Route::post('meal-orders/bulk-apply', [MealOrderController::class, 'bulkApply']);
    Route::post('meal-orders/manual-apply', [MealOrderController::class, 'manualApply']);
    Route::post('meal-orders/copy-from-previous-day', [MealOrderController::class, 'copyFromPreviousDay']);
    Route::post('meal-orders/stat', [MealOrderController::class, 'statOrder']);
    Route::post('meal-orders/{mealOrder}/advance', [MealOrderController::class, 'advance']);
    Route::post('meal-orders/{mealOrder}/reject', [MealOrderController::class, 'reject']);

    Route::post('sync/hosxp', [SyncController::class, 'run']);
    Route::post('notifications/telegram/test', [TelegramController::class, 'test']);
});
