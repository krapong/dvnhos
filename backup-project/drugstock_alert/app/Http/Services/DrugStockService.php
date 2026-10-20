<?php

namespace App\Http\Services;

use App\Models\SystemSetting;
use App\Models\AlertNotification;
use App\Models\Drug;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockLot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DrugStockService
{
    public function dashboardMetrics(?int $fiscalYear = null): array
    {
        $expiryDays = (int) SystemSetting::getValue('expiry_alert_days', config('medstock.stock_policy.expiry_alert_days', 90));
        [$startDate, $endDate] = $this->getFiscalYearDates($fiscalYear);

        return [
            'warehouse_count' => Warehouse::query()->where('is_active', true)->count(),
            'drug_count' => Drug::query()->where('is_active', true)->count(),
            'low_stock_count' => WarehouseStock::query()
                ->join('drugs', 'warehouse_stocks.drug_id', '=', 'drugs.id')
                ->whereColumn('warehouse_stocks.qty_on_hand', '<=', 'drugs.min_stock_qty')
                ->count(),
            'near_expiry_count' => WarehouseStockLot::query()
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now(), now()->addDays($expiryDays)])
                ->where('qty_on_hand', '>', 0)
                ->count(),
            'expired_count' => WarehouseStockLot::query()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now())
                ->where('qty_on_hand', '>', 0)
                ->count(),
            'movement_today' => StockMovement::query()
                ->whereDate('movement_at', today())
                ->count(),
            'movement_in_fy' => StockMovement::query()
                ->whereBetween('movement_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    public function receive(array $payload, int $userId): StockMovement
    {
        return DB::transaction(function () use ($payload, $userId) {
            $movement = StockMovement::create([
                'movement_no' => $this->movementNo('RCV'),
                'type' => 'receive',
                'warehouse_id' => $payload['warehouse_id'],
                'performed_by' => $userId,
                'status' => 'completed',
                'movement_at' => $payload['movement_at'] ?? now(),
                'note' => $payload['note'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'total_qty' => 0,
                'total_cost' => 0,
            ]);

            $totalQty = 0;
            $totalCost = 0;

            foreach ($payload['items'] as $item) {
                $stock = WarehouseStock::query()->firstOrCreate(
                    [
                        'warehouse_id' => $payload['warehouse_id'],
                        'drug_id' => $item['drug_id'],
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                    ]
                );

                $stock->increment('qty_on_hand', $item['quantity']);
                $stock->forceFill(['last_movement_at' => $movement->movement_at])->save();

                $lot = WarehouseStockLot::query()->firstOrCreate(
                    [
                        'warehouse_stock_id' => $stock->id,
                        'lot_no' => $item['lot_no'],
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ],
                    [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                        'unit_cost' => $item['unit_cost'] ?? 0,
                        'received_date' => $item['received_date'] ?? now()->toDateString(),
                        'status' => 'active',
                    ]
                );

                $lot->increment('qty_on_hand', $item['quantity']);
                $lot->forceFill([
                    'unit_cost' => $item['unit_cost'] ?? $lot->unit_cost,
                    'received_date' => $item['received_date'] ?? $lot->received_date,
                    'status' => 'active',
                ])->save();

                $lineTotal = round(((float) $item['unit_cost']) * ((float) $item['quantity']), 2);

                StockMovementItem::create([
                    'stock_movement_id' => $movement->id,
                    'drug_id' => $item['drug_id'],
                    'warehouse_stock_lot_id' => $lot->id,
                    'lot_no' => $item['lot_no'],
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'line_total' => $lineTotal,
                    'reason' => $item['reason'] ?? null,
                ]);

                $totalQty += (float) $item['quantity'];
                $totalCost += $lineTotal;
            }

            $movement->update([
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
            ]);

            return $movement->load(['items', 'warehouse']);
        });
    }

    public function issue(array $payload, int $userId): StockMovement
    {
        return DB::transaction(function () use ($payload, $userId) {
            $movement = StockMovement::create([
                'movement_no' => $this->movementNo('ISS'),
                'type' => 'issue',
                'warehouse_id' => $payload['warehouse_id'],
                'performed_by' => $userId,
                'status' => 'completed',
                'movement_at' => $payload['movement_at'] ?? now(),
                'note' => $payload['note'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'total_qty' => 0,
                'total_cost' => 0,
            ]);

            $totalQty = 0;
            $totalCost = 0;

            foreach ($payload['items'] as $item) {
                $qty = (float) $item['quantity'];
                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $payload['warehouse_id'])
                    ->where('drug_id', $item['drug_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    throw ValidationException::withMessages([
                        'items' => ['ไม่พบ stock ของยารายการนี้ในคลัง'],
                    ]);
                }

                $remaining = $qty;
                $lots = WarehouseStockLot::query()
                    ->where('warehouse_stock_id', $stock->id)
                    ->where('qty_on_hand', '>', 0)
                    ->orderByRaw(SystemSetting::getValue('default_allocation_rule', config('medstock.stock_policy.default_allocation_rule', 'FEFO')) === 'FIFO'
                        ? 'id asc'
                        : 'coalesce(expiry_date, \'9999-12-31\') asc, id asc')
                    ->lockForUpdate()
                    ->get();

                $consumed = 0;
                $consumedCost = 0;

                foreach ($lots as $lot) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $take = min($remaining, (float) $lot->qty_on_hand);
                    if ($take <= 0) {
                        continue;
                    }

                    $lot->decrement('qty_on_hand', $take);
                    $stock->decrement('qty_on_hand', $take);

                    $lineCost = round($take * (float) $lot->unit_cost, 2);
                    StockMovementItem::create([
                        'stock_movement_id' => $movement->id,
                        'drug_id' => $item['drug_id'],
                        'warehouse_stock_lot_id' => $lot->id,
                        'lot_no' => $lot->lot_no,
                        'expiry_date' => $lot->expiry_date,
                        'quantity' => -$take,
                        'unit_cost' => $lot->unit_cost,
                        'line_total' => -$lineCost,
                        'reason' => $item['reason'] ?? null,
                    ]);

                    $remaining -= $take;
                    $consumed += $take;
                    $consumedCost += $lineCost;
                }

                if ($remaining > 0) {
                    throw ValidationException::withMessages([
                        'items' => ['สต็อกยาไม่เพียงพอสำหรับรายการที่เลือก'],
                    ]);
                }

                $stock->forceFill(['last_movement_at' => $movement->movement_at])->save();

                $totalQty += $consumed;
                $totalCost += $consumedCost;
            }

            $movement->update([
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
            ]);

            return $movement->load(['items', 'warehouse']);
        });
    }

    public function adjust(array $payload, int $userId): StockMovement
    {
        return DB::transaction(function () use ($payload, $userId) {
            $movement = StockMovement::create([
                'movement_no' => $this->movementNo('ADJ'),
                'type' => 'adjust',
                'warehouse_id' => $payload['warehouse_id'],
                'performed_by' => $userId,
                'status' => 'completed',
                'movement_at' => $payload['movement_at'] ?? now(),
                'note' => $payload['note'] ?? null,
                'meta' => $payload['meta'] ?? null,
                'total_qty' => 0,
                'total_cost' => 0,
            ]);

            $totalQty = 0;
            $totalCost = 0;

            foreach ($payload['items'] as $item) {
                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $payload['warehouse_id'])
                    ->where('drug_id', $item['drug_id'])
                    ->lockForUpdate()
                    ->firstOrCreate([
                        'warehouse_id' => $payload['warehouse_id'],
                        'drug_id' => $item['drug_id'],
                    ], [
                        'qty_on_hand' => 0,
                        'qty_reserved' => 0,
                    ]);

                $lot = WarehouseStockLot::query()
                    ->where('warehouse_stock_id', $stock->id)
                    ->where('lot_no', $item['lot_no'])
                    ->when(
                        filled($item['expiry_date'] ?? null),
                        fn ($query) => $query->whereDate('expiry_date', $item['expiry_date']),
                        fn ($query) => $query->whereNull('expiry_date')
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $lot) {
                    throw ValidationException::withMessages([
                        'items' => ['ไม่พบ lot ที่ต้องการตัด stock'],
                    ]);
                }

                $delta = (float) $item['quantity'];
                if (($item['direction'] ?? 'decrease') === 'decrease') {
                    $delta = -abs($delta);
                }

                if ($lot->qty_on_hand + $delta < 0) {
                    throw ValidationException::withMessages([
                        'items' => ['จำนวนตัด stock มากกว่าคงเหลือใน lot'],
                    ]);
                }

                $lot->increment('qty_on_hand', $delta);
                $stock->increment('qty_on_hand', $delta);
                $stock->forceFill(['last_movement_at' => $movement->movement_at])->save();

                $lineCost = round(abs($delta) * (float) $lot->unit_cost, 2);
                StockMovementItem::create([
                    'stock_movement_id' => $movement->id,
                    'drug_id' => $item['drug_id'],
                    'warehouse_stock_lot_id' => $lot->id,
                    'lot_no' => $lot->lot_no,
                    'expiry_date' => $lot->expiry_date,
                    'quantity' => $delta,
                    'unit_cost' => $lot->unit_cost,
                    'line_total' => $delta < 0 ? -$lineCost : $lineCost,
                    'reason' => $item['reason'] ?? null,
                ]);

                $totalQty += abs($delta);
                $totalCost += $lineCost;
            }

            $movement->update([
                'total_qty' => $totalQty,
                'total_cost' => $totalCost,
            ]);

            return $movement->load(['items', 'warehouse']);
        });
    }

    public function movements(array $filters = [])
    {
        return StockMovement::query()
            ->with(['warehouse', 'performer', 'items.drug'])
            ->when($filters['warehouse_id'] ?? null, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('movement_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('movement_at', '<=', $date))
            ->orderByDesc('movement_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function currentStocks(array $filters = [])
    {
        $result = WarehouseStock::query()
            ->with(['warehouse.location', 'drug.category', 'lots' => fn ($query) => $query->orderByRaw('coalesce(expiry_date, \'9999-12-31\') asc')])
            ->when($filters['warehouse_id'] ?? null, fn ($query, $warehouseId) => $query->where('warehouse_id', $warehouseId))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->whereHas('drug', function ($drugQuery) use ($search) {
                    $drugQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('qty_on_hand')
            ->paginate((int) ($filters['per_page'] ?? 15));
            
        \Illuminate\Support\Facades\Log::info('currentStocks called', [
            'warehouse_id' => $filters['warehouse_id'] ?? null,
            'result_count' => count($result->items()),
            'user' => auth()->id()
        ]);
        
        return $result;
    }

    public function alertItems(): Collection
    {
        return WarehouseStockLot::query()
            ->with(['stock.warehouse.location', 'stock.drug.category'])
            ->where(function ($query) {
                $query->where('qty_on_hand', '<=', 0)
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('expiry_date')
                            ->whereBetween('expiry_date', [now(), now()->addDays(config('medstock.stock_policy.expiry_alert_days', 90))]);
                    });
            })
            ->orderByRaw('coalesce(expiry_date, \'9999-12-31\') asc')
            ->get();
    }

    public function getFiscalYearDates(?int $year = null): array
    {
        if (!$year) {
            $now = now();
            $year = $now->month >= 10 ? $now->year + 1 : $now->year;
        }
        $start = \Carbon\Carbon::create($year - 1, 10, 1)->startOfDay();
        $end = \Carbon\Carbon::create($year, 9, 30)->endOfDay();
        return [$start, $end];
    }

    private function movementNo(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, now()->format('YmdHis'), Str::upper(Str::random(4)));
    }
}
