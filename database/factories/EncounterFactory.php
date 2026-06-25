<?php

namespace Database\Factories;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Encounter> */
class EncounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'     => Patient::factory(),
            'provider_id'    => Provider::factory(),
            'appointment_id' => null,
            'encounter_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'subjective'     => fake()->optional()->sentence(6),
            'objective'      => fake()->optional()->sentence(6),
            'assessment'     => fake()->optional()->sentence(4),
            'plan'           => fake()->optional()->sentence(5),
            'is_locked'      => false,
            'locked_at'      => null,
            'locked_by'      => null,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['is_locked' => true]);
    }
}
