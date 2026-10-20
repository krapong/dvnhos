<?php

namespace Database\Factories;

use App\Models\Bed;
use Illuminate\Database\Eloquent\Factories\Factory;

class BedFactory extends Factory
{
    protected $model = Bed::class;

    public function definition(): array
    {
        return [
            'ward_id' => null,
            'room_id' => null,
            'code' => (string) $this->faker->unique()->numberBetween(1, 50),
            'name' => 'Bed '.$this->faker->numberBetween(1, 50),
            'is_isolation' => false,
            'active' => true,
        ];
    }
}
