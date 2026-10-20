<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'diet_type_id',
        'code',
        'name',
        'description',
        'target_energy_kcal',
        'target_protein_g',
        'target_carbohydrate_g',
        'target_fat_g',
        'target_sodium_mg',
        'target_potassium_mg',
        'target_fluid_ml',
        'texture',
        'allowed_for_notes',
        'restricted_for_notes',
        'meal_scope',
        'is_active',
    ];

    protected $casts = [
        'target_energy_kcal' => 'decimal:2',
        'target_protein_g' => 'decimal:2',
        'target_carbohydrate_g' => 'decimal:2',
        'target_fat_g' => 'decimal:2',
        'target_sodium_mg' => 'decimal:2',
        'target_potassium_mg' => 'decimal:2',
        'target_fluid_ml' => 'decimal:2',
        'allowed_for_notes' => 'array',
        'restricted_for_notes' => 'array',
        'meal_scope' => 'array',
        'is_active' => 'boolean',
    ];

    public function dietType(): BelongsTo
    {
        return $this->belongsTo(DietType::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DietTemplateItem::class);
    }
}
