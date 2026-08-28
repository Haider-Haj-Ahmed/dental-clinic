<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Operatory;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', '+30 days');
        $end   = (clone $start)->modify('+' . $this->faker->randomElement([30, 45, 60, 90]) . ' minutes');

        return [
            'patient_id'          => Patient::factory(),
            'provider_id'         => Provider::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'operatory_id'        => Operatory::factory(),
            'created_by'          => User::factory(),
            'start_at'            => $start,
            'end_at'              => $end,
            'status'              => $this->faker->randomElement(['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show']),
            'notes'               => $this->faker->optional(.3)->sentence(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => 'scheduled', 'start_at' => $this->faker->dateTimeBetween('+1 day', '+30 days')]);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed', 'start_at' => $this->faker->dateTimeBetween('-30 days', '-1 day')]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function noShow(): static
    {
        return $this->state(['status' => 'no_show']);
    }

    public function today(): static
    {
        $start = now()->setTime($this->faker->numberBetween(8, 17), 0);
        return $this->state(['start_at' => $start, 'end_at' => (clone $start)->addMinutes(60), 'status' => 'confirmed']);
    }

    /** Bind to an existing patient — useful in tests to avoid extra rows */
    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(['provider_id' => $provider->id]);
    }
}
