<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppointmentType> */
class AppointmentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                     => fake()->unique()->words(2, true),
            'default_duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'color'                    => fake()->hexColor(),
            'is_active'                => true,
        ];
    }
}
