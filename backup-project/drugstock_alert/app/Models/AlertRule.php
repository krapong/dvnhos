<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'scope',
        'warehouse_id',
        'location_id',
        'drug_id',
        'alert_type',
        'threshold_qty',
        'expiry_days',
        'is_enabled',
        'notify_telegram',
    ];

    protected function casts(): array
    {
        return [
            'threshold_qty' => 'decimal:3',
            'expiry_days' => 'integer',
            'is_enabled' => 'boolean',
            'notify_telegram' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}
