<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    protected $fillable = [
        'movement_no',
        'type',
        'warehouse_id',
        'source_warehouse_id',
        'destination_warehouse_id',
        'reference_type',
        'reference_id',
        'performed_by',
        'approved_by',
        'total_qty',
        'total_cost',
        'status',
        'movement_at',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'movement_at' => 'datetime',
            'total_qty' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
