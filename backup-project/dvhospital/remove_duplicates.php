<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pages = App\Models\Page::where('section', 'like', 'ita%')->get();
$totalDeleted = 0;

foreach ($pages as $page) {
    if ($page->attachments && count($page->attachments) > 0) {
        $originalCount = count($page->attachments);
        
        $uniqueAttachments = [];
        $seenNames = [];
        
        foreach ($page->attachments as $file) {
            // We consider files duplicate if they have the exact same name
            if (!in_array($file['name'], $seenNames)) {
                $seenNames[] = $file['name'];
                $uniqueAttachments[] = $file;
            } else {
                // This is a duplicate!
                // Optionally delete the physical file as well
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file['path'])) {
                    // Be careful with physical deletion if both array entries point to the SAME path.
                    // If they point to different paths, we should delete the file.
                    // Let's just keep the physical file for safety and remove the DB entry.
                }
                echo "Removing duplicate file: " . $file['name'] . " from Page ID " . $page->id . "\n";
            }
        }
        
        if (count($uniqueAttachments) < $originalCount) {
            $page->attachments = $uniqueAttachments;
            $page->save();
            $deleted = $originalCount - count($uniqueAttachments);
            $totalDeleted += $deleted;
            echo "Saved Page ID {$page->id}, removed {$deleted} duplicates.\n";
        }
    }
}

echo "Total duplicates removed: {$totalDeleted}\n";
