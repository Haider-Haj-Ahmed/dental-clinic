<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Provider> */
class ProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => null,
            'operatory_id'   => null,
            'name'           => 'Dr. '.fake()->name(),
            'specialty'      => fake()->randomElement([
                'General Dentistry', 'Orthodontics', 'Endodontics',
                'Periodontics', 'Oral Surgery', 'Pediatric Dentistry',
            ]),
            'phone'          => fake()->phoneNumber(),
            'email'          => fake()->unique()->safeEmail(),
            'license_number' => strtoupper(fake()->bothify('DEN-#####')),
            'bio'            => fake()->optional()->paragraph(),
            'is_active'      => true,
        ];
    }

    public function linkedUser(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => User::ROLE_PROVIDER]),
        ]);
    }
}
