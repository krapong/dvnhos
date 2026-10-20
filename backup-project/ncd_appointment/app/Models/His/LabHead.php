<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabHead extends Model
{
    use HasFactory;

    protected $connection = 'hosxp';
    protected $table = 'lab_head';
    protected $primaryKey = 'lab_order_number';
    public $timestamps = false;
}
