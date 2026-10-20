<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NcdWeeklyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinic_id',
        'fiscal_year',
        'month',
        'week_no',
        'patient_count',
    ];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
