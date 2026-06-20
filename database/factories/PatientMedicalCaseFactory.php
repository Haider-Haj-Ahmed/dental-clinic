<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientMedicalCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PatientMedicalCase> */
class PatientMedicalCaseFactory extends Factory
{
    public function definition(): array
    {
        $isExternal = fake()->boolean(20); // 20% chance of external case

        return [
            'patient_id'          => Patient::factory(),
            'provider_id'         => null,
            'case_date'           => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'case_type'           => fake()->randomElement(PatientMedicalCase::TYPES),
            'chief_complaint'     => fake()->optional()->sentence(4),
            'diagnosis'           => fake()->optional()->sentence(6),
            'treatment_performed' => fake()->optional()->sentence(8),
            'outcome'             => fake()->optional()->sentence(5),
            'is_external'         => $isExternal,
            'previous_clinic'     => $isExternal ? fake()->company().' Dental' : null,
            'previous_dentist'    => $isExternal ? 'Dr. '.fake()->name() : null,
            'notes'               => fake()->optional()->paragraph(),
            'created_by'          => User::factory(),
        ];
    }
}
