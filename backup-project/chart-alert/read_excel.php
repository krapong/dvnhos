<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xls';
$inputFileName = 'สรุปข้อมูลรับบริการ .xls';
$reader = IOFactory::createReader($inputFileType);
$spreadsheet = $reader->load($inputFileName);

$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
$count = 0;
foreach ($sheetData as $row) {
    echo $row['A'] . "\t|\t" . $row['B'] . "\t|\t" . $row['C'] . "\t|\t" . $row['D'] . "\t|\t" . $row['E'] . "\t|\t" . $row['F'] . "\n";
    if ($count++ > 25) break;
}
