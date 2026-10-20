<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pages = App\Models\Page::where('section', 'ita')->get();
foreach($pages as $p) {
    echo "Page ID: {$p->id} Title: {$p->title}\n";
    if($p->attachments) {
        foreach($p->attachments as $i => $a) {
            echo "  [$i] {$a['name']}\n";
        }
    }
}
