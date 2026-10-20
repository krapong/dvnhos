<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $clinics = \App\Models\Clinic::where('is_active', true)->get();
    $operatingDays = \App\Models\ClinicOperatingDay::with('clinic')->get();
    
    $events = $operatingDays->map(function($day) {
        return [
            'id' => $day->id,
            'title' => $day->clinic->name,
            'start' => $day->date,
            'allDay' => true,
            'backgroundColor' => $day->clinic->color_code,
            'borderColor' => $day->clinic->color_code,
            'extendedProps' => [
                'clinic_id' => $day->clinic_id,
                'is_holiday' => false
            ]
        ];
    })->values()->toArray();

    // Add holidays to events as background blocks
    $holidays = \App\Models\Holiday::all();
    foreach ($holidays as $holiday) {
        $events[] = [
            'id' => 'h_' . $holiday->id,
            'title' => $holiday->name,
            'start' => $holiday->date,
            'allDay' => true,
            'display' => 'background',
            'backgroundColor' => '#fecaca', // red-200
            'extendedProps' => [
                'is_holiday' => true,
                'name' => $holiday->name
            ]
        ];
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'clinics' => $clinics,
        'events' => $events
    ]);
});

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');
Route::get('/dashboard/details', [\App\Http\Controllers\DashboardDetailController::class, 'getDetails'])
    ->middleware(['auth', 'verified'])->name('dashboard.details');
Route::get('/dashboard/export', [\App\Http\Controllers\DashboardDetailController::class, 'export'])
    ->middleware(['auth', 'verified'])->name('dashboard.export');

Route::get('/appointments/list', function () { return Inertia::render('Appointments/List'); })->middleware(['auth', 'verified'])->name('appointments.list');

// Services
Route::get('/services/tracking', function () { return Inertia::render('Services/Tracking'); })->middleware(['auth', 'verified'])->name('services.tracking');
Route::get('/services/labs', function () { return Inertia::render('Services/Labs'); })->middleware(['auth', 'verified'])->name('services.labs');

// Follow Ups
Route::get('/follow-ups/missed', function () { return Inertia::render('FollowUps/Missed'); })->middleware(['auth', 'verified'])->name('followups.missed');
Route::get('/follow-ups/tracking', function () { return Inertia::render('FollowUps/Tracking'); })->middleware(['auth', 'verified'])->name('followups.tracking');
Route::get('/follow-ups/patients', function () { return Inertia::render('FollowUps/Patients'); })->middleware(['auth', 'verified'])->name('followups.patients');

// Reports
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/preview', [\App\Http\Controllers\ReportController::class, 'preview'])->name('reports.preview');
    Route::get('/reports/export/excel', [\App\Http\Controllers\ReportController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [\App\Http\Controllers\ReportController::class, 'exportPdf'])->name('reports.export.pdf');
});
// Clinics
Route::get('/clinics', [\App\Http\Controllers\ClinicController::class, 'index'])->middleware(['auth', 'verified', 'role:admin'])->name('clinics.index');
Route::put('/clinics/{code}', [\App\Http\Controllers\ClinicController::class, 'update'])->middleware(['auth', 'verified', 'role:admin'])->name('clinics.update');

// Admin routes (Spatie role middleware)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/hosxp-sync', [\App\Http\Controllers\Admin\HosxpSyncController::class, 'index'])->name('hosxp-sync');
    Route::post('/hosxp-sync', [\App\Http\Controllers\Admin\HosxpSyncController::class, 'sync'])->name('hosxp-sync.process');

    Route::get('/audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-log');

    // Users Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users');
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Roles Management
    Route::get('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'index'])->name('roles');
    Route::post('/roles', [\App\Http\Controllers\Admin\RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [\App\Http\Controllers\Admin\RoleController::class, 'destroy'])->name('roles.destroy');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings');
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/clinics/holidays', [\App\Http\Controllers\HolidayController::class, 'index'])->name('clinics.holidays');
    Route::post('/clinics/holidays', [\App\Http\Controllers\HolidayController::class, 'store'])->name('clinics.holidays.store');
    Route::delete('/clinics/holidays/{id}', [\App\Http\Controllers\HolidayController::class, 'destroy'])->name('clinics.holidays.destroy');
    
    Route::get('/clinics/capacity', [\App\Http\Controllers\CapacityController::class, 'index'])->name('clinics.capacity');
    Route::put('/clinics/capacity/{id}', [\App\Http\Controllers\CapacityController::class, 'update'])->name('clinics.capacity.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Appointment routes
    Route::get('/appointments', [\App\Http\Controllers\ClinicOperatingDayController::class, 'index'])->name('appointments.index');
    Route::post('/appointments', [\App\Http\Controllers\ClinicOperatingDayController::class, 'store'])->name('appointments.store');
    Route::delete('/appointments/{id}', [\App\Http\Controllers\ClinicOperatingDayController::class, 'destroy'])->name('appointments.destroy');
    
    Route::get('/appointments/daily', [\App\Http\Controllers\DashboardController::class, 'index'])->name('appointments.daily');
    Route::get('/appointments/schedule', [\App\Http\Controllers\AppointmentController::class, 'scheduleBoard'])->name('appointments.schedule');
});

// NCD Stats
Route::middleware(['auth', 'verified', 'can:manage ncd stats'])->prefix('ncd-stats')->name('ncd-stats.')->group(function () {
    Route::get('/data-entry', [\App\Http\Controllers\NcdStatController::class, 'dataEntry'])->name('data-entry');
    Route::post('/store', [\App\Http\Controllers\NcdStatController::class, 'store'])->name('store');
    Route::get('/summary', [\App\Http\Controllers\NcdStatController::class, 'summary'])->name('summary');
});

require __DIR__.'/auth.php';
// โค้ดทางลัดสำหรับรันคำสั่งบน Server
Route::get('/run-cmd', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('app:fetch-holidays');
        
        return "อัปเดตฐานข้อมูลและวันหยุดสำเร็จแล้ว! กลับไปที่หน้าเว็บได้เลยครับ";
    } catch (\Exception $e) {
        return "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
});
