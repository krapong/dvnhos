<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'patients_cache';

    protected $fillable = [
        'hn',
        'an',
        'prefix',
        'first_name',
        'last_name',
        'ward_id',
        'room_id',
        'bed_id',
        'sex',
        'date_of_birth',
        'weight_kg',
        'height_cm',
        'bmi',
        'bmi_taken_at',
        'npo_status',
        'diet_order',
        'therapeutic_diet',
        'specific_group_diet',
        'food_allergy',
        'primary_diagnosis',
        'secondary_diagnosis',
        'clinical_note',
        'source_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'bmi_taken_at' => 'datetime',
        'synced_at' => 'datetime',
        'npo_status' => 'boolean',
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(Bed::class);
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function getRouteKeyName(): string
    {
        return 'hn';
    }
}
