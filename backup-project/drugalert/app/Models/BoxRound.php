<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxRound extends Model
{
    const UPDATED_AT = null;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'box_id',
        'round_number',
        'status',
        'created_by',
        'items_count',
        'used_count',
        'used_value',
    ];

    public function box()
    {
        return $this->belongsTo(Box::class);
    }

    public function items()
    {
        return $this->hasMany(BoxRoundItem::class, 'box_round_id');
    }
}
