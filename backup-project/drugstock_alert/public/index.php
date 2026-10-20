<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/drugstock_alert') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/drugstock_alert/index.php';
}

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
