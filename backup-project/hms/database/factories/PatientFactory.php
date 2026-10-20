<?php

namespace Database\Factories;

use App\Models\Bed;
use App\Models\Patient;
use App\Models\Room;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        $ward = Ward::factory()->create();
        $room = Room::factory()->create(['ward_id' => $ward->id]);
        $bed = Bed::factory()->create(['ward_id' => $ward->id, 'room_id' => $room->id]);

        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'hn' => 'HN'.$this->faker->unique()->numerify('#####'),
            'an' => 'AN'.$this->faker->unique()->numerify('#####'),
            'prefix' => $this->faker->randomElement(['นาย', 'นาง', 'นางสาว']),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'ward_id' => $ward->id,
            'room_id' => $room->id,
            'bed_id' => $bed->id,
            'sex' => $this->faker->randomElement(['M', 'F']),
            'date_of_birth' => $this->faker->date(),
            'weight_kg' => $this->faker->randomFloat(2, 40, 90),
            'height_cm' => $this->faker->randomFloat(2, 145, 185),
            'bmi' => $this->faker->randomFloat(2, 18, 30),
            'bmi_taken_at' => now(),
            'npo_status' => false,
            'diet_order' => 'NORMAL',
            'therapeutic_diet' => null,
            'primary_diagnosis' => $this->faker->word(),
            'secondary_diagnosis' => $this->faker->word(),
            'clinical_note' => null,
            'source_updated_at' => now(),
            'synced_at' => now(),
        ];
    }
}
