<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => null,
            'employee_code' => 'EMP-' . $this->faker->unique()->numerify('####'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'position_title' => $this->faker->jobTitle(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
