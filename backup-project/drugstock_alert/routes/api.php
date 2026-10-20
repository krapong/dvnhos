<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DrugStock\AlertController as DrugStockAlertController;
use App\Http\Controllers\Api\V1\DrugStock\DashboardController as DrugStockDashboardController;
use App\Http\Controllers\Api\V1\DrugStock\DrugController as DrugStockDrugController;
use App\Http\Controllers\Api\V1\DrugStock\LocationController as DrugStockLocationController;
use App\Http\Controllers\Api\V1\DrugStock\ReportController as DrugStockReportController;
use App\Http\Controllers\Api\V1\DrugStock\RoleController as DrugStockRoleController;
use App\Http\Controllers\Api\V1\DrugStock\SettingController as DrugStockSettingController;
use App\Http\Controllers\Api\V1\DrugStock\StockController as DrugStockStockController;
use App\Http\Controllers\Api\V1\DrugStock\UserController as DrugStockUserController;
use App\Http\Controllers\Api\V1\DrugStock\WarehouseController as DrugStockWarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::prefix('drugstock')->group(function () {
            Route::get('/dashboard', [DrugStockDashboardController::class, 'overview'])->middleware('permission:dashboard.view');

            Route::get('/warehouses', [DrugStockWarehouseController::class, 'index'])->middleware('permission:warehouse.view');
            Route::get('/warehouses/{warehouse}', [DrugStockWarehouseController::class, 'show'])->middleware('permission:warehouse.view');
            Route::post('/warehouses', [DrugStockWarehouseController::class, 'store'])->middleware('permission:warehouse.manage');
            Route::put('/warehouses/{warehouse}', [DrugStockWarehouseController::class, 'update'])->middleware('permission:warehouse.manage');
            Route::delete('/warehouses/{warehouse}', [DrugStockWarehouseController::class, 'destroy'])->middleware('permission:warehouse.manage');

            Route::get('/locations', [DrugStockLocationController::class, 'index'])->middleware('permission:location.view');
            Route::post('/locations', [DrugStockLocationController::class, 'store'])->middleware('permission:location.manage');
            Route::put('/locations/{location}', [DrugStockLocationController::class, 'update'])->middleware('permission:location.manage');
            Route::delete('/locations/{location}', [DrugStockLocationController::class, 'destroy'])->middleware('permission:location.manage');

            Route::get('/drugs', [DrugStockDrugController::class, 'index'])->middleware('permission:drug.view');
            Route::get('/drugs/options', [DrugStockDrugController::class, 'options'])->middleware('permission:drug.view');
            Route::post('/drugs', [DrugStockDrugController::class, 'store'])->middleware('permission:drug.manage');
            Route::put('/drugs/{drug}', [DrugStockDrugController::class, 'update'])->middleware('permission:drug.manage');
            Route::delete('/drugs/{drug}', [DrugStockDrugController::class, 'destroy'])->middleware('permission:drug.manage');

            Route::get('/stocks/current', [DrugStockStockController::class, 'current'])->middleware('permission:stock.view');
            Route::get('/stocks/history', [DrugStockStockController::class, 'history'])->middleware('permission:stock.view');
            Route::post('/stocks/receive', [DrugStockStockController::class, 'receive'])->middleware('permission:stock.manage');
            Route::post('/stocks/issue', [DrugStockStockController::class, 'issue'])->middleware('permission:stock.manage');
            Route::post('/stocks/adjust', [DrugStockStockController::class, 'adjust'])->middleware('permission:stock.manage');

            Route::get('/alerts', [DrugStockAlertController::class, 'index'])->middleware('permission:alert.view');
            Route::get('/alerts/timeline', [DrugStockAlertController::class, 'timeline'])->middleware('permission:alert.view');
            Route::post('/alerts/rules', [DrugStockAlertController::class, 'store'])->middleware('permission:alert.manage');
            Route::put('/alerts/rules/{alertRule}', [DrugStockAlertController::class, 'update'])->middleware('permission:alert.manage');
            Route::post('/alerts/rules/presets', [DrugStockAlertController::class, 'storePresets'])->middleware('permission:alert.manage');
            Route::get('/alerts/rounds', [DrugStockAlertController::class, 'rounds'])->middleware('permission:alert.view');
            Route::patch('/alerts/{alertNotification}/resolve', [DrugStockAlertController::class, 'resolve'])->middleware('permission:alert.resolve');

            Route::get('/reports/stock-summary', [DrugStockReportController::class, 'stockSummary'])->middleware('permission:report.view');
            Route::get('/reports/expiry-summary', [DrugStockReportController::class, 'expirySummary'])->middleware('permission:report.view');
            Route::get('/reports/adjustment-summary', [DrugStockReportController::class, 'adjustmentSummary'])->middleware('permission:report.view');
            Route::get('/reports/alert-summary', [DrugStockReportController::class, 'alertSummary'])->middleware('permission:report.view');
            Route::get('/reports/export', [DrugStockReportController::class, 'export'])->middleware('permission:report.view');

            Route::get('/roles', [DrugStockRoleController::class, 'index'])->middleware('permission:role.manage');
            Route::put('/roles/{role}', [DrugStockRoleController::class, 'update'])->middleware('permission:role.manage');

            Route::get('/settings', [DrugStockSettingController::class, 'index'])->middleware('permission:settings.manage');
            Route::put('/settings', [DrugStockSettingController::class, 'update'])->middleware('permission:settings.manage');
            Route::post('/settings/telegram/test', [DrugStockSettingController::class, 'testTelegram'])->middleware('permission:telegram.manage');

            Route::get('/users', [DrugStockUserController::class, 'index'])->middleware('permission:user.view');
            Route::put('/users/{user}', [DrugStockUserController::class, 'update'])->middleware('permission:user.manage');
            Route::patch('/users/{user}/status', [DrugStockUserController::class, 'updateStatus'])->middleware('permission:user.manage');
            Route::put('/users/{user}/roles', [DrugStockUserController::class, 'updateRoles'])->middleware('permission:user.manage');
            Route::delete('/users/{user}', [DrugStockUserController::class, 'destroy'])->middleware('permission:user.manage');
        });
    });
});
