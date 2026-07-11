<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class MedicalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
{
    return [
        'patient_id' => \App\Models\Patient::inRandomOrder()->first()->id,
        'doctor_id' => \App\Models\Doctor::inRandomOrder()->first()->id,
        'appointment_id' => \App\Models\Appointment::inRandomOrder()->first()->id,
        'record_type' => fake()->randomElement(['diagnosis', 'lab_result', 'prescription', 'radiology']),
        'diagnosis' => fake()->sentence(),
        'notes' => fake()->paragraph(),
    ];
}
}
