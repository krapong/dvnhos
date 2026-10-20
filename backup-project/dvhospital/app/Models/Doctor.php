<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $fillable = ['name', 'title', 'specialty', 'photo', 'bio', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function getFullNameAttribute(): string
    {
        return ($this->title ? $this->title . ' ' : '') . $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
