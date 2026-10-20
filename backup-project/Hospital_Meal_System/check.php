<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Schedules:\n";
echo App\Models\TelegramNotificationSchedule::all()->toJson(JSON_PRETTY_PRINT);
echo "\nLogs (last 3):\n";
echo App\Models\TelegramLog::latest()->take(3)->get()->toJson(JSON_PRETTY_PRINT);
