<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = [
        'id',
        'box_id',
        'box_name',
        'location_name',
        'drug_name',
        'quantity',
        'unit',
        'severity',
        'status',
        'created_at',
        'expiry_date',
        'resolved_at',
        'category',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    const UPDATED_AT = null;

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_id');
    }
}
