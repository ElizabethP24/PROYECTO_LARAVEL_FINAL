<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d');
        $time = $this->faker->time('H:i');

        // create slug from date/time + uniqid to ensure non-null
        $slug = Str::slug($date . '-' . $time . '-' . uniqid());

        return [
            'id_patient' => \App\Models\Patient::factory(),
            'id_doctor' => \App\Models\Doctor::factory(),
            'id_specialty' => \App\Models\Specialty::factory(),
            'date' => $date,
            'time' => $time,
            'status' => $this->faker->randomElement(['pending', 'approved']),
            'slug' => $slug,
        ];
    }
}
