<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ClinicOperatingDay extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['date', 'clinic_id'];

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['date', 'clinic_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
