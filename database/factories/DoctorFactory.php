<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
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
    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'phone' => fake()->phoneNumber(),
        'date_of_birth' => fake()->date(),
        'specialty' => fake()->randomElement(['Cardiology', 'Dentist', 'Pediatrician', 'Dermatologist']),
        'address' => fake()->address(),
        'years_of_experience' => fake()->numberBetween(1, 20),
        'credential_document' => 'path/to/docs/' . fake()->word() . '.pdf',
        'national_id' => fake()->unique()->numerify('##########'),
        'status' => 'active',
        'gender' => fake()->randomElement(['male', 'female']),
    ];
}
}
