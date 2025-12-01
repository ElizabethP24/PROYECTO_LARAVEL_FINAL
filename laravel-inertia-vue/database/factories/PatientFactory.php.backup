<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'document' => strtoupper($this->faker->bothify('ID#######')),
            'email' => $this->faker->unique()->safeEmail(),
            'eps' => $this->faker->randomElement(['SURA', 'Coomeva', 'Sanitas', 'Nueva EPS', 'Compensar']),
        ];
    }
}
