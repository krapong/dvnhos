<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xls';
$inputFileName = 'สรุปข้อมูลรับบริการ .xls';
$reader = IOFactory::createReader($inputFileType);
$spreadsheet = $reader->load($inputFileName);

$sheet = $spreadsheet->getSheetByName('ha');
if ($sheet) {
    $data = $sheet->toArray(null, true, true, true);
    $c = 0;
    foreach ($data as $row) {
        $r = array_filter($row, function($v) { return trim((string)$v) !== ''; });
        if (!empty($r)) {
            print_r($r);
            $c++;
        }
        if ($c >= 10) break;
    }
} else {
    echo "Sheet HA not found.";
}
