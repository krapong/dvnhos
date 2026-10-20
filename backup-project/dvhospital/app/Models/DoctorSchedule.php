<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id', 'day_of_week', 'start_time', 'end_time',
        'clinic', 'note', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getDayLabelAttribute(): string
    {
        return match($this->day_of_week) {
            'monday' => 'จันทร์',
            'tuesday' => 'อังคาร',
            'wednesday' => 'พุธ',
            'thursday' => 'พฤหัสบดี',
            'friday' => 'ศุกร์',
            'saturday' => 'เสาร์',
            'sunday' => 'อาทิตย์',
            default => $this->day_of_week,
        };
    }
}
