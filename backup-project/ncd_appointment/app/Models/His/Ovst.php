<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ovst extends Model
{
    use HasFactory;

    protected $connection = 'hosxp';
    protected $table = 'ovst';
    protected $primaryKey = 'vn';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
