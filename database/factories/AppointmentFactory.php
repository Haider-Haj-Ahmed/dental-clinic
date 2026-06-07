<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('+1 day', '+30 days');
        $endAt   = (clone $startAt)->modify('+45 minutes');

        return [
            'patient_id'          => Patient::factory(),
            'provider_id'         => Provider::factory(),
            'appointment_type_id' => null,
            'operatory_id'        => null,
            'start_at'            => $startAt->format('Y-m-d H:i:s'),
            'end_at'              => $endAt->format('Y-m-d H:i:s'),
            'status'              => Appointment::STATUS_SCHEDULED,
            'chief_complaint'     => fake()->optional()->sentence(4),
            'notes'               => fake()->optional()->paragraph(),
            'color'               => null,
            'created_by'          => User::factory()->state(['role' => User::ROLE_RECEPTIONIST]),
            'cancelled_at'        => null,
            'reminder_sent_at'    => null,
        ];
    }
}
