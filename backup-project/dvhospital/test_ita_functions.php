<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- STARTING COMPREHENSIVE ITA FUNCTION TESTS ---\n\n";

// 1. Regex Matching Tests (PHP)
echo "1. Testing PHP Regex Matching (Used for displaying files):\n";
$testCases = [
    // [ItemKey, Filename, Expected Match]
    ['1.2', '1.2 แบบฟอร์ม', true],
    ['1.2', '1.2.1 แบบฟอร์ม', false], // Should not match
    ['1.2', '1.22 แบบฟอร์ม', false],  // Should not match
    ['1.2', 'เอกสาร 1.2', true],
    ['1.2', '1.2.pdf', false], // Wait, is this expected? 
    ['3', '3.1 แบบฟอร์ม', false],
    ['3', '3 แบบฟอร์ม', true],
    ['3', 'เอกสารข้อ 3', true],
    ['3.1', '3.11 แบบฟอร์ม', false],
    ['3.1', '13.1 แบบฟอร์ม', false],
];

foreach ($testCases as $case) {
    $itemKey = $case[0];
    $filename = $case[1];
    $expected = $case[2];
    
    $regex = '/(?:^|\s)' . preg_quote($itemKey, '/') . '(?!\d|\.)/u';
    $matches = preg_match($regex, trim($filename));
    $passed = (bool)$matches === $expected;
    
    echo "Key: '{$itemKey}' | File: '{$filename}' -> Match: " . ($matches ? 'YES' : 'NO') . " [" . ($passed ? 'PASS' : 'CHANGE_ME_BEFORE_USE') . "]\n";
}

echo "\n2. Testing PageController Logic (Simulated):\n";
// Create a temporary page for testing
$page = App\Models\Page::firstOrCreate(
    ['slug' => 'test-ita-functions-page'],
    ['title' => 'Test ITA', 'section' => 'ita', 'content' => 'test', 'is_active' => true, 'user_id' => 1]
);

// Reset attachments
$page->update(['attachments' => []]);
echo "Page initialized with 0 attachments.\n";

// TEST UPLOAD
echo "\n--- Upload Test ---\n";
function simulateUpload($page, $name, $key) {
    if (!str_contains($name, $key)) {
        $name = $key . ' ' . $name;
    }
    $attachments = $page->attachments ?? [];
    foreach ($attachments as $att) {
        if ($att['name'] === $name) {
            return "ERROR: Duplicate Name";
        }
    }
    $attachments[] = ['name' => $name, 'path' => 'test/path.pdf', 'size' => 1024];
    $page->update(['attachments' => $attachments]);
    return "SUCCESS";
}

echo "Upload 1 ('1.2'): " . simulateUpload($page, '1.2 แบบฟอร์ม', '1.2') . "\n";
echo "Upload 2 ('1.2' duplicate): " . simulateUpload($page, '1.2 แบบฟอร์ม', '1.2') . "\n";
echo "Upload 3 ('1.2.1'): " . simulateUpload($page, '1.2.1 แบบฟอร์ม', '1.2.1') . "\n";
echo "Upload 4 ('3.1'): " . simulateUpload($page, '3.1 แบบฟอร์ม', '3.1') . "\n";

echo "Current Attachments:\n";
foreach($page->fresh()->attachments as $i => $a) echo "  [$i] {$a['name']}\n";

// TEST EDIT
echo "\n--- Edit Test ---\n";
function simulateEdit($page, $index, $newName) {
    $attachments = $page->attachments ?? [];
    if (!isset($attachments[$index])) return "ERROR: Not Found";
    
    foreach ($attachments as $i => $att) {
        if ($i !== (int)$index && $att['name'] === $newName) {
            return "ERROR: Duplicate Name";
        }
    }
    $attachments[$index]['name'] = $newName;
    $page->update(['attachments' => $attachments]);
    return "SUCCESS";
}

echo "Edit Index 2 (1.2.1) to '1.2.1 แบบฟอร์มใหม่': " . simulateEdit($page, 2, '1.2.1 แบบฟอร์มใหม่') . "\n";
echo "Edit Index 2 (1.2.1) to '1.2 แบบฟอร์ม' (Should fail): " . simulateEdit($page, 2, '1.2 แบบฟอร์ม') . "\n";

echo "Current Attachments:\n";
foreach($page->fresh()->attachments as $i => $a) echo "  [$i] {$a['name']}\n";

// TEST DELETE
echo "\n--- Delete Test ---\n";
function simulateDelete($page, $index) {
    $attachments = $page->attachments ?? [];
    if (!isset($attachments[$index])) return "ERROR: Not Found";
    
    unset($attachments[$index]);
    $attachments = array_values($attachments);
    $page->update(['attachments' => $attachments]);
    return "SUCCESS";
}

echo "Deleting Index 0 (1.2 แบบฟอร์ม): " . simulateDelete($page, 0) . "\n";
echo "Current Attachments (Notice index shift!):\n";
foreach($page->fresh()->attachments as $i => $a) echo "  [$i] {$a['name']}\n";

// Cleanup
$page->delete();
echo "\nTest cleanup complete.\n";
