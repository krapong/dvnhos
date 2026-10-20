<?php

namespace Database\Factories;

use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class WardFactory extends Factory
{
    protected $model = Ward::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->bothify('W##'));

        return [
            'code' => $code,
            'name' => "Ward {$code}",
            'floor' => $this->faker->randomElement(['2F', '3F', '4F']),
            'active' => true,
        ];
    }
}
