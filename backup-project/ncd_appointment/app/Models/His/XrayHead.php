<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class XrayHead extends Model
{
    use HasFactory;
    
    protected $connection = 'hosxp';
    protected $table = 'xray_head';
    protected $primaryKey = 'vn';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
