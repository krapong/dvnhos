<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xls';
$inputFileName = 'สรุปข้อมูลรับบริการ .xls';
$reader = IOFactory::createReader($inputFileType);
$spreadsheet = $reader->load($inputFileName);

foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
    echo "=== Sheet: $sheetName ===\n";
    $sheet = $spreadsheet->getSheet($index);
    $data = $sheet->toArray(null, true, true, true);
    $c = 0;
    foreach ($data as $row) {
        // filter empty cols for cleaner output
        $r = array_filter($row, function($v) { return trim((string)$v) !== ''; });
        if (!empty($r)) {
            print_r($r);
            $c++;
        }
        if ($c >= 3) break;
    }
    echo "\n";
}
