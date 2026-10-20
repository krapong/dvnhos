<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'd:/backup project/chart-alert/สรุปข้อมูลรับบริการ .xls';
$reader = IOFactory::createReader('Xls');
$spreadsheet = $reader->load($file);

foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
    echo "Sheet $index: $sheetName\n";
    $sheet = $spreadsheet->getSheet($index);
    $data = $sheet->toArray();
    print_r(array_slice($data, 0, 30));
}
