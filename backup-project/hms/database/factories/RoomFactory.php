<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'ward_id' => null,
            'code' => strtoupper($this->faker->unique()->bothify('R##')),
            'name' => $this->faker->words(2, true),
        ];
    }
}
