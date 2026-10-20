<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$categories = \App\Models\DrugCategory::all();
$units = \App\Models\Unit::all();
echo json_encode([
    'categories' => $categories,
    'units' => $units
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
