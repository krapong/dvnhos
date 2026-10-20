<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pages = App\Models\Page::all();
foreach ($pages as $page) {
    if ($page->attachments) {
        $found = false;
        foreach ($page->attachments as $att) {
            if (strpos($att['name'], '3.1') !== false || strpos($att['name'], '1.2.2') !== false) {
                $found = true;
            }
        }
        if ($found) {
            echo "Page ID: " . $page->id . "\n";
            echo "Title: " . $page->title . "\n";
            print_r($page->attachments);
            echo "---------------------------------\n";
        }
    }
}
