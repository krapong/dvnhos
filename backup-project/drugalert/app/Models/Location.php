<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    const CREATED_AT = null;
}
