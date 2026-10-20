<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use App\Http\Controllers\ToolsController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::get('/tools', [ToolsController::class, 'index']);
Route::get('/tools/clear-cache', function () {
    \Artisan::call('optimize:clear');
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    return "Cache and OPCache cleared! Please go back and try logging in again.";
});

Route::post('/tools/login', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::query()
        ->where(function($query) use ($request) {
            $query->where('email', $request->username)
                  ->orWhere('employee_code', $request->username);
        })
        ->where('is_active', true)
        ->first();

    if (! $user || (!in_array($user->status, ['active', null])) || (
        $user->password_salt && $user->password_hash
            ? hash_hmac('sha256', $request->password, $user->password_salt) !== $user->password_hash
            : (! $user->password || ! Hash::check($request->password, $user->password))
    )) {
        return back()->withInput()->with('error', 'Login failed');
    }

    auth()->login($user);
    $request->session()->regenerate();

    return redirect('/tools');
});
Route::post('/tools/logout', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/tools');
});

Route::middleware('auth')->group(function () {
    Route::get('/api/events', function (Request $request) {
        return response()->stream(function () use ($request) {
            echo ": connected\n\n";
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            if (function_exists('flush')) {
                @flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    });

    Route::get('/tools/export/{resource}.csv', [\App\Http\Controllers\DataTransferController::class, 'export']);
    Route::get('/tools/export/{resource}.xlsx', [\App\Http\Controllers\DataTransferController::class, 'export']);
    Route::get('/tools/report.pdf', [\App\Http\Controllers\DataTransferController::class, 'reportPdf']);
    Route::post('/tools/import/{resource}', [\App\Http\Controllers\DataTransferController::class, 'import']);
});

// For the SPA, we just return the index view for all routes.
// The Vue/Vanilla JS app will handle client-side routing and views.
Route::get('{any}', function () {
    return view('index');
})->where('any', '.*');
