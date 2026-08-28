<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'patient_id'     => Patient::factory(),
            'appointment_id' => null,
            'status'         => 'draft',
            'finalized_by'   => null,
            'finalized_at'   => null,
            'voided_by'      => null,
            'voided_at'      => null,
            'notes'          => $this->faker->optional(.2)->sentence(),
        ];
    }

    /**
     * Finalized invoice.
     * Uses a lazy closure — User::factory() is resolved at creation time,
     * NOT at factory definition time (fixes the ->create() inside state bug).
     */
    public function finalized(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status'       => 'finalized',
                'finalized_by' => User::factory(),
                'finalized_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            ];
        });
    }

    public function voided(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status'   => 'voided',
                'voided_by'=> User::factory(),
                'voided_at'=> now()->subDays($this->faker->numberBetween(1, 7)),
            ];
        });
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }
}
