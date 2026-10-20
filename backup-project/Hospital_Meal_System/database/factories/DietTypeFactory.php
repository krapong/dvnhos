<?php

namespace Database\Factories;

use App\Models\DietType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DietTypeFactory extends Factory
{
    protected $model = DietType::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->bothify('DT##'));

        return [
            'code' => $code,
            'name' => "Diet {$code}",
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
