<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => 'Dr. '.fake()->name(),
            'specialty' => fake()->randomElement(['General Dentistry', 'Orthodontics', 'Endodontics']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'license_number' => strtoupper(fake()->bothify('DEN-#####')),
            'is_active' => true,
        ];
    }

    public function linkedUser(): static
    {
        return $this->state(function (): array {
            return [
                'user_id' => User::factory()->state(['role' => User::ROLE_PROVIDER]),
            ];
        });
    }
}
