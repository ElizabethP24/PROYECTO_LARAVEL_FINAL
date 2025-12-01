<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
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
        do {
            $document = $this->faker->numerify('##########');
        } while (DB::table('doctors')->where('document', $document)->exists() || DB::table('patients')->where('document', $document)->exists());

        $slug = Str::slug($name . '-' . uniqid());
        $existingSpecialty = \App\Models\Specialty::inRandomOrder()->first();
        return [
            'name' => $name,
            'document' => $document,
            'email' => $this->faker->unique()->safeEmail(),
            'id_specialty' => $existingSpecialty ? $existingSpecialty->id_specialty : \App\Models\Specialty::factory(),
            'user_id' => \App\Models\User::factory()->state(['role' => 'doctor']),
            'status' => $this->faker->randomElement(['Activo', 'Inactivo']),
            'slug' => $slug,
        ];
    }
}
