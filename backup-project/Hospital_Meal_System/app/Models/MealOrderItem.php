<?php

namespace App\Models;

use App\Enums\MealType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealOrderItem extends Model
{
    protected $fillable = [
        'meal_order_id',
        'meal_type',
        'diet_template_id',
        'diet_type_id',
        'menu_item_name',
        'portion',
        'status',
        'notes',
    ];

    protected $casts = [
        'meal_type' => MealType::class,
        'status' => OrderStatus::class,
        'notes' => 'array',
    ];

    public function mealOrder(): BelongsTo
    {
        return $this->belongsTo(MealOrder::class);
    }
}
