<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/files/{path}', function (string $path) {
    $filePath = base_path('files/' . $path);

    abort_unless(file_exists($filePath), 404);

    return Response::file($filePath);
})->where('path', '.*');

Route::get('/clear-cache', function() {
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    return 'Cache cleared successfully. Please go back and refresh the page.';
});
