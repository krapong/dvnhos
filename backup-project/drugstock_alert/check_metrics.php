<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WarehouseStock;
use App\Models\WarehouseStockLot;
use App\Models\Drug;
use Illuminate\Support\Facades\DB;

echo "--- Negative Stocks ---\n";
$negativeStocks = WarehouseStock::where('qty_on_hand', '<', 0)->get();
foreach($negativeStocks as $stock) {
    echo "Warehouse: {$stock->warehouse_id}, Drug: {$stock->drug_id}, Qty: {$stock->qty_on_hand}\n";
}

echo "\n--- Total Qty by Warehouse ---\n";
$warehouseTotals = WarehouseStock::select('warehouse_id', DB::raw('SUM(qty_on_hand) as total'))->groupBy('warehouse_id')->get();
foreach($warehouseTotals as $total) {
    echo "Warehouse {$total->warehouse_id}: {$total->total}\n";
}

echo "\n--- Low Stock Check (Current Query) ---\n";
$currentQuery = WarehouseStock::query()
    ->join('drugs', 'warehouse_stocks.drug_id', '=', 'drugs.id')
    ->whereColumn('warehouse_stocks.qty_on_hand', '<=', 'drugs.min_stock_qty')
    ->count();
echo "Count: $currentQuery\n";

echo "\n--- Low Stock Check (Grouped by Drug) ---\n";
$groupedQuery = Drug::query()
    ->select('drugs.*', DB::raw('COALESCE(SUM(warehouse_stocks.qty_on_hand), 0) as total_qty'))
    ->leftJoin('warehouse_stocks', 'drugs.id', '=', 'warehouse_stocks.drug_id')
    ->where('drugs.is_active', true)
    ->groupBy('drugs.id')
    ->havingRaw('total_qty <= drugs.min_stock_qty')
    ->get();
echo "Count grouped by drug: " . $groupedQuery->count() . "\n";
