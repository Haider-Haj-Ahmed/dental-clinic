<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EncounterFactory extends Factory
{
    protected $model = Encounter::class;

    public function definition(): array
    {
        return [
            'patient_id'     => Patient::factory(),
            'provider_id'    => Provider::factory(),
            'appointment_id' => null,
            'encounter_date' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'subjective'     => $this->faker->paragraph(),
            'objective'      => $this->faker->paragraph(),
            'assessment'     => $this->faker->sentence(),
            'plan'           => $this->faker->paragraph(),
            'is_locked'      => false,
            'locked_by'      => null,
            'locked_at'      => null,
        ];
    }

    /**
     * Locked encounter — locked_by is a User id (not Provider id).
     * Uses a lazy closure so the User is only created when the factory runs.
     */
    public function locked(): static
    {
        return $this->state(function (array $attributes) {
            $lockedBy = User::factory()->provider()->create();

            return [
                'is_locked' => true,
                'locked_by' => $lockedBy->id,
                'locked_at' => now()->subMinutes($this->faker->numberBetween(10, 120)),
            ];
        });
    }

    public function unlocked(): static
    {
        return $this->state([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(['provider_id' => $provider->id]);
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state([
            'appointment_id' => $appointment->id,
            'patient_id'     => $appointment->patient_id,
            'provider_id'    => $appointment->provider_id,
        ]);
    }
}
