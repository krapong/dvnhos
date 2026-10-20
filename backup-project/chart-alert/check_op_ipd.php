<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/backup project/chart-alert/สรุปข้อมูลรับบริการ .xls';
$reader = IOFactory::createReader('Xls');
$spreadsheet = $reader->load($file);

foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
    if (strpos(strtolower($sheetName), 'op') !== false && strpos(strtolower($sheetName), '10') === false) {
        $sheet = $spreadsheet->getSheet($index);
        echo "=== SHEET: $sheetName ===\n";
        print_r(array_slice($sheet->toArray(), 0, 20));
    }
    if (strpos(strtolower($sheetName), 'ipd') !== false && strpos(strtolower($sheetName), '10') === false) {
        $sheet = $spreadsheet->getSheet($index);
        echo "=== SHEET: $sheetName ===\n";
        print_r(array_slice($sheet->toArray(), 0, 20));
    }
}
