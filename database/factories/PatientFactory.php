<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name'              => fake()->firstName(),
            'last_name'               => fake()->lastName(),
            'gender'                  => fake()->randomElement(['male', 'female', 'other']),
            'date_of_birth'           => fake()->dateTimeBetween('-80 years', '-3 years')->format('Y-m-d'),
            'phone'                   => fake()->phoneNumber(),
            'email'                   => fake()->optional()->safeEmail(),
            'address'                 => fake()->optional()->address(),
            'emergency_contact_name'  => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->phoneNumber(),
            'notes'                   => fake()->optional()->paragraph(),
            'is_active'               => true,
        ];
    }
}
