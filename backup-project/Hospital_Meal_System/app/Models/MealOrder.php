<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MealOrder extends Model
{
    protected $fillable = [
        'meal_order_batch_id',
        'patient_id',
        'admission_id',
        'ward_id',
        'room_id',
        'bed_id',
        'meal_date',
        'status',
        'ordered_by',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'accepted_at',
        'changed_by',
        'change_reason',
        'source',
        'is_stat',
        'npo_locked',
    ];

    protected $casts = [
        'meal_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_stat' => 'boolean',
        'npo_locked' => 'boolean',
        'status' => OrderStatus::class,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MealOrderBatch::class, 'meal_order_batch_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealOrderItem::class);
    }
}
