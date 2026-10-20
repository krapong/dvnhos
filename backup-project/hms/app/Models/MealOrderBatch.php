<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealOrderBatch extends Model
{
    protected $fillable = [
        'ward_id',
        'service_date',
        'cutoff_time',
        'status',
        'created_by',
        'submitted_at',
        'approved_at',
        'kitchen_accepted_at',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'kitchen_accepted_at' => 'datetime',
        'notes' => 'array',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MealOrder::class);
    }
}
