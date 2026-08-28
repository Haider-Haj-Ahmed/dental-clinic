<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'role'              => fake()->randomElement(User::ROLES),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function owner(): static
    {
        return $this->state(['role' => User::ROLE_OWNER]);
    }

    public function provider(): static
    {
        return $this->state(['role' => User::ROLE_PROVIDER]);
    }

    public function receptionist(): static
    {
        return $this->state(['role' => User::ROLE_RECEPTIONIST]);
    }

    public function assistant(): static
    {
        return $this->state(['role' => User::ROLE_ASSISTANT]);
    }
}
