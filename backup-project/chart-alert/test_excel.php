<?php
ini_set('memory_limit', '-1');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$inputFile = 'D:/backup project/chart-alert/9.10.69  เช้า.xlsx';
try {
    $reader = new Xlsx();
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($inputFile);
    
    $sheet = $spreadsheet->getSheetByName('export');
    if ($sheet === null) {
        echo "Sheet 'export' not found.\n";
        echo "Available sheets: " . implode(', ', $spreadsheet->getSheetNames()) . "\n";
    } else {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        echo "Highest Row: $highestRow, Highest Column: $highestColumn\n";
        
        for ($row = 1; $row <= min(30, $highestRow); $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= 'G'; $col++) {
                $val = $sheet->getCell($col . $row)->getValue();
                $rowData[] = $val;
            }
            echo "Row $row: " . implode(" | ", $rowData) . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
