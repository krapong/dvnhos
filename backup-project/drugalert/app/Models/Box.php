<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = null;

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function rounds()
    {
        return $this->hasMany(BoxRound::class, 'box_id');
    }
}
