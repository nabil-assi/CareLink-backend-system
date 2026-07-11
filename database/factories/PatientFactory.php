<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
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
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
        'phone' => fake()->phoneNumber(),
        'date_of_birth' => fake()->date(),
        'national_id' => fake()->unique()->numerify('##########'),
        'address' => fake()->address(),
        'status' => 'active',
        'gender' => fake()->randomElement(['male', 'female']),
    ];
}
}
