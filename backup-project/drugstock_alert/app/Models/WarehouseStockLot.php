<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseStockLot extends Model
{
    protected $fillable = [
        'warehouse_stock_id',
        'lot_no',
        'expiry_date',
        'qty_on_hand',
        'qty_reserved',
        'unit_cost',
        'received_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_date' => 'date',
            'qty_on_hand' => 'decimal:3',
            'qty_reserved' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'warehouse_stock_id');
    }
}
