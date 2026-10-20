<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Time: " . now()->toDateTimeString() . "\n";
echo "Config Timezone: " . config('app.timezone') . "\n";
$current = now()->format('H:i');
echo "Current H:i : " . $current . "\n";

$schedules = \App\Models\TelegramNotificationSchedule::all();
foreach($schedules as $s) {
    echo "Schedule: {$s->key} -> {$s->notify_at}\n";
}

$count = \App\Models\TelegramNotificationSchedule::query()->whereTime('notify_at', $current)->count();
echo "Match count using whereTime: " . $count . "\n";
