<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'generic_name',
        'drug_category_id',
        'unit_id',
        'barcode',
        'min_stock_qty',
        'reorder_qty',
        'is_controlled',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'min_stock_qty' => 'decimal:3',
            'reorder_qty' => 'decimal:3',
            'is_controlled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrugCategory::class, 'drug_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }
}
