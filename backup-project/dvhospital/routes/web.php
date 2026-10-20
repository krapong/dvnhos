<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PopupController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Admin\SopController as AdminSopController;
use App\Http\Controllers\Frontend\SopController as FrontendSopController;

/*
|--------------------------------------------------------------------------
| Frontend Routes (สาธารณะ)
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/services', [HomeController::class, 'services'])->name('services');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/ita', [HomeController::class, 'ita'])->name('ita');
Route::get('/ethics', [HomeController::class, 'ethics'])->name('ethics');
Route::get('/doctor-schedule', [HomeController::class, 'doctorSchedule'])->name('doctor-schedule');
Route::get('/calendar', [HomeController::class, 'calendar'])->name('calendar');
Route::get('/ncd', [HomeController::class, 'ncd'])->name('ncd');
Route::get('/api/calendar-events', [HomeController::class, 'calendarData'])->name('api.calendar');

// Blog Listings
Route::get('/news', [HomeController::class, 'news'])->name('news.index');
Route::get('/jobs', [HomeController::class, 'jobs'])->name('jobs.index');
Route::get('/procurement', [HomeController::class, 'procurement'])->name('procurement.index');
Route::get('/activities', [HomeController::class, 'activities'])->name('activities.index');

// Single Post / Page
Route::get('/post/{slug}', [HomeController::class, 'showPost'])->name('post.show');
Route::get('/page/{slug}', [HomeController::class, 'showPage'])->name('page.show');

// SOP Documents
Route::get('/sops', [FrontendSopController::class, 'index'])->name('sops.index');
Route::get('/sops/download/{id}', [FrontendSopController::class, 'download'])->name('sops.download');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Routes (Backend - ต้อง Login + มี Role)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // User Management (Super Admin only)
    Route::resource('users', UserController::class)->except(['show']);

    // ข่าวประชาสัมพันธ์ (Staff PR)
    Route::resource('news', PostController::class)->except(['show'])
        ->middleware('permission:manage_news');

    // รับสมัครงาน (Staff PR)
    Route::resource('jobs', PostController::class)->except(['show'])
        ->middleware('permission:manage_jobs')
        ->parameters(['jobs' => 'post']);

    // จัดซื้อจัดจ้าง (Staff Procurement)
    Route::resource('procurement', PostController::class)->except(['show'])
        ->middleware('permission:manage_procurement')
        ->parameters(['procurement' => 'post']);

    // กิจกรรม (Staff Events)
    Route::resource('activities', PostController::class)->except(['show'])
        ->middleware('permission:manage_activities')
        ->parameters(['activities' => 'post']);

    // Banners (Staff PR)
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::resource('popups', PopupController::class)->except(['show']);

    // ตารางแพทย์ (Super Admin)
    Route::resource('doctors', DoctorController::class)->except(['show']);

    // ปฏิทินกิจกรรม (Staff Events)
    Route::resource('events', EventController::class)->except(['show', 'create', 'edit']);
    Route::get('events/calendar-data', [EventController::class, 'calendarData'])->name('events.calendar-data');

    // หน้าเพจ ITA / Ethics / OIT (Staff ITA)
    Route::post('pages/{page}/upload-attachment', [PageController::class, 'uploadAttachment'])->name('pages.upload-attachment');
    Route::post('pages/{page}/update-attachment', [PageController::class, 'updateAttachment'])->name('pages.update-attachment');
    Route::post('pages/{page}/delete-attachment', [PageController::class, 'deleteAttachment'])->name('pages.delete-attachment');
    Route::resource('pages', PageController::class)->except(['show']);

    // Activity Logs (Super Admin only)
    Route::get('logs', [LogController::class, 'index'])->name('logs.index');

    // SOP Documents
    Route::resource('sops', AdminSopController::class)->except(['show'])
        ->middleware('permission:manage_sops');
});

/*
|--------------------------------------------------------------------------
| Local Development Storage Fallback
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    Route::get('/storage/{path}', function ($path) {
        $filePath = storage_path('app/public/' . $path);
        if (file_exists($filePath)) {
            $mimeType = mime_content_type($filePath);
            return response()->file($filePath, ['Content-Type' => $mimeType]);
        }
        abort(404);
    })->where('path', '.*');
}
