<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealOrderChange extends Model
{
    protected $fillable = [
        'meal_order_id',
        'changed_by',
        'change_type',
        'before_payload',
        'after_payload',
        'reason',
    ];

    protected $casts = [
        'before_payload' => 'array',
        'after_payload' => 'array',
    ];
}
