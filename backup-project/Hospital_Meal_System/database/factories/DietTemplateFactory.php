<?php

namespace Database\Factories;

use App\Models\DietTemplate;
use App\Models\DietType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DietTemplateFactory extends Factory
{
    protected $model = DietTemplate::class;

    public function definition(): array
    {
        return [
            'diet_type_id' => DietType::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('TPL-###')),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'target_energy_kcal' => 1600,
            'target_protein_g' => 60,
            'target_carbohydrate_g' => 200,
            'target_fat_g' => 50,
            'target_sodium_mg' => 1500,
            'target_potassium_mg' => 2000,
            'target_fluid_ml' => 1500,
            'texture' => 'soft',
            'allowed_for_notes' => ['standard'],
            'restricted_for_notes' => ['shrimp'],
            'meal_scope' => ['breakfast', 'lunch', 'dinner'],
            'is_active' => true,
        ];
    }
}
