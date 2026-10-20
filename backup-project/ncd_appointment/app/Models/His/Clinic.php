<?php

namespace App\Models\His;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    // Point to HOSxP connection
    protected $connection = 'hosxp';
    
    // Point to HOSxP table
    protected $table = 'clinic';
    
    // HOSxP usually uses 'clinic' as primary key
    protected $primaryKey = 'clinic';
    public $incrementing = false;
    protected $keyType = 'string';
    
    // HOSxP doesn't have timestamps
    public $timestamps = false;
}
