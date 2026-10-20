<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $request = Illuminate\Http\Request::create('/auth/telegram/schedules/test', 'POST', [
        'key' => 'dinner'
    ]);
    
    $request->headers->set('Accept', 'application/json');
    $controller = app(App\Http\Controllers\Api\TelegramScheduleController::class);
    $response = $controller->test($request);
    echo $response->getContent();
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
