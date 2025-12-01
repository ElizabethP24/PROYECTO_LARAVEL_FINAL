<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

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
        $firstNames = ['María', 'Pedro', 'David', 'Ana', 'Luis', 'Carmen', 'Ricardo', 'Sofia', 'Diego', 'Marta', 'Jorge', 'Lucía', 'Andrés', 'Elena'];
        $lastNames = ['Gómez', 'Pérez', 'Rodríguez', 'López', 'Martínez', 'García', 'Sánchez', 'Ramírez', 'Cruz', 'Flores', 'Rivera', 'Torres', 'Gonzalez', 'Hernandez'];

        $name = $this->faker->randomElement($firstNames) . ' ' . $this->faker->randomElement($lastNames);

        // Generate a unique 10-digit numeric document not present in patients or doctors
        do {
            $document = $this->faker->numerify('##########');
        } while (DB::table('patients')->where('document', $document)->exists() || DB::table('doctors')->where('document', $document)->exists());

        return [
            'name' => $name,
            'document' => $document,
            'email' => $this->faker->unique()->safeEmail(),
            'eps' => $this->faker->randomElement(['SURA', 'Coomeva', 'Sanitas', 'Nueva EPS', 'Compensar']),
        ];
    }
}
