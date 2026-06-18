<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditLog> */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'action'     => fake()->randomElement([AuditLog::ACTION_CREATED, AuditLog::ACTION_UPDATED, AuditLog::ACTION_DELETED]),
            'model_type' => Patient::class,
            'model_id'   => fake()->numberBetween(1, 100),
            'old_values' => null,
            'new_values' => ['first_name' => fake()->firstName()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
