<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Clinic extends Model
{
    use LogsActivity;

    // Local appointment codes and HOSxP service-department codes are not
    // identical for every clinic (COPD is 011 in oapp and 023 in ovst).
    public static function hosxpMainDep(string $clinicCode): string
    {
        return [
            '001' => '015',
            '002' => '022',
            '004' => '086',
            '009' => '032',
            '011' => '023',
            '021' => '024',
            '028' => '034',
        ][$clinicCode] ?? $clinicCode;
    }

    protected $fillable = ['code', 'name', 'color_code', 'description', 'is_active', 'max_appointments'];

    public function schedules()
    {
        return $this->hasMany(ClinicSchedule::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function operatingDays()
    {
        return $this->hasMany(ClinicOperatingDay::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'color_code', 'is_active', 'max_appointments'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
