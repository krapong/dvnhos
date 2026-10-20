<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$drug = \App\Models\Drug::first();
echo json_encode($drug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
