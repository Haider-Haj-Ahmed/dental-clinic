<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Recall;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recall> */
class RecallFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'due_date'   => fake()->dateTimeBetween('-1 month', '+6 months')->format('Y-m-d'),
            'status'     => fake()->randomElement(Recall::STATUSES),
            'notes'      => fake()->optional()->sentence(),
        ];
    }

    public function due(): static
    {
        return $this->state(fn () => [
            'due_date' => now()->subDays(fake()->numberBetween(0, 30))->toDateString(),
            'status'   => Recall::STATUS_PENDING,
        ]);
    }
}
