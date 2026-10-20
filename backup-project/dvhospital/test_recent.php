<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = App\Models\ActivityLog::orderBy('created_at', 'desc')->take(20)->get();
foreach($logs as $log) {
    echo "[$log->created_at] $log->action: $log->description\n";
}
