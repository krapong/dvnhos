<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$drugs = \App\Models\Drug::all(['id', 'name', 'code']);
echo json_encode($drugs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
