<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$patients = \App\Models\Patient::all();

$total = $patients->count();
$male = $patients->where('sex', '1')->count();
$female = $patients->where('sex', '2')->count();

$withOrder = 0;
$normal = 0;
$therapeutic = 0;
$npo = 0;
$highBmi = 0;

foreach ($patients as $p) {
    if ($p->bmi && ($p->bmi < 18.5 || $p->bmi >= 30)) {
        $highBmi++;
    }
    
    if ($p->npo_status) {
        $npo++;
        $withOrder++;
        continue;
    }
    
    $hasMeal = false;
    if (!empty($p->diet_order) || (str_contains((string)$p->clinical_note, 'รายการอาหาร HIS'))) {
        $hasMeal = true;
    }
    
    if ($hasMeal) {
        $withOrder++;
        if (!empty($p->therapeutic_diet)) {
            $therapeutic++;
        } else {
            $normal++;
        }
    }
}

$withoutOrder = $total - $withOrder;

echo "Total: $total\n";
echo "Male: $male, Female: $female\n";
echo "With Order: $withOrder, Without: $withoutOrder\n";
echo "Normal: $normal, Therapeutic: $therapeutic\n";
echo "NPO: $npo\n";
echo "High BMI: $highBmi\n";
