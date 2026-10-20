<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovementItem extends Model
{
    protected $fillable = [
        'stock_movement_id',
        'drug_id',
        'warehouse_stock_lot_id',
        'lot_no',
        'expiry_date',
        'quantity',
        'unit_cost',
        'line_total',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'stock_movement_id');
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(WarehouseStockLot::class, 'warehouse_stock_lot_id');
    }
}
