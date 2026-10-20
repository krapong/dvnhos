<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keywords = [
    '1.2.4',
    '1.2.5',
    '1.2.6',
    '2.',
    '2.1 ',
    '2.2 ',
    '2.3 ',
    '2.4 ',
    '2.5 ',
];

$logs = App\Models\ActivityLog::where(function($query) use ($keywords) {
    foreach($keywords as $keyword) {
        $query->orWhere('description', 'like', "%{$keyword}%");
    }
})->orderBy('created_at', 'desc')->get();

echo "จำนวนประวัติที่พบ: " . $logs->count() . "\n";
foreach($logs as $log) {
    echo "[{$log->created_at}] {$log->action}: {$log->description}\n";
}
