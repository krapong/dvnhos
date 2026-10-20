<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoxRoundItem extends Model
{
    const UPDATED_AT = null;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'box_round_id',
        'drug_id',
        'drug_name',
        'lot_number',
        'quantity',
        'used_quantity',
        'expiry_date',
        'alert_days',
    ];

    public function round()
    {
        return $this->belongsTo(BoxRound::class, 'box_round_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }
}
