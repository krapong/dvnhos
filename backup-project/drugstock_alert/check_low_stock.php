<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Drug;
use Illuminate\Support\Facades\DB;

$groupedQuery = Drug::query()
    ->select('drugs.id', 'drugs.min_stock_qty', DB::raw('COALESCE(SUM(warehouse_stocks.qty_on_hand), 0) as total_qty'))
    ->leftJoin('warehouse_stocks', 'drugs.id', '=', 'warehouse_stocks.drug_id')
    ->where('drugs.is_active', true)
    ->groupBy('drugs.id', 'drugs.min_stock_qty')
    ->havingRaw('total_qty <= drugs.min_stock_qty')
    ->get();
    
echo "Global low stock count: " . $groupedQuery->count() . "\n";
