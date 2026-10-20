<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$drugs = [
    ['name' => 'Pethidine 50 mg/ml', 'min_stock_qty' => 5, 'drug_category_id' => 2, 'unit_id' => 1],
    ['name' => 'Ampicillin 500 mg', 'min_stock_qty' => 8, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Cefazolin 1 g', 'min_stock_qty' => 2, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Cefotaxime 1 g', 'min_stock_qty' => 4, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Ceftriaxone 1 g', 'min_stock_qty' => 6, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Ceftazidime 1 g', 'min_stock_qty' => 6, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Chloramphenicol 1 g', 'min_stock_qty' => 4, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Cloxacillin 1 g', 'min_stock_qty' => 4, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Gentamicin 80 mg/2ml', 'min_stock_qty' => 6, 'drug_category_id' => 8, 'unit_id' => 1],
    ['name' => 'Metronidazole 500 mg/vial', 'min_stock_qty' => 2, 'drug_category_id' => 8, 'unit_id' => 2],
    ['name' => 'Alum milk 240 ml', 'min_stock_qty' => 1, 'drug_category_id' => 3, 'unit_id' => 4],
    ['name' => 'KCL elixir 30 ml', 'min_stock_qty' => 5, 'drug_category_id' => 2, 'unit_id' => 4],
    ['name' => 'M. Carminative 180 ml', 'min_stock_qty' => 1, 'drug_category_id' => 3, 'unit_id' => 4],
    ['name' => 'Kalimate 5 g', 'min_stock_qty' => 12, 'drug_category_id' => 3, 'unit_id' => 5],
    ['name' => 'Paracetamol 120 mg/5ml', 'min_stock_qty' => 2, 'drug_category_id' => 3, 'unit_id' => 4],
    ['name' => 'Domperidone 5 mg/5 ml', 'min_stock_qty' => 2, 'drug_category_id' => 3, 'unit_id' => 4],
    ['name' => 'Unison 20 ml', 'min_stock_qty' => 1, 'drug_category_id' => 3, 'unit_id' => 5],
    ['name' => 'Unison 100 ml', 'min_stock_qty' => 1, 'drug_category_id' => 3, 'unit_id' => 5],
];

$count = 0;
foreach ($drugs as $index => $item) {
    // Check if exists
    if (!\App\Models\Drug::where('name', $item['name'])->exists()) {
        \App\Models\Drug::create(array_merge($item, [
            'code' => '990' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
            'generic_name' => $item['name'],
            'reorder_qty' => max(1, floor($item['min_stock_qty'] / 2)),
            'is_active' => true,
            'is_controlled' => ($item['drug_category_id'] == 2) ? true : false,
        ]));
        $count++;
    }
}
echo "Added $count missing drugs.\n";
