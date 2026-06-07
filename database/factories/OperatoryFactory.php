<?php

namespace Database\Factories;

use App\Models\Operatory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Operatory> */
class OperatoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => 'Chair '.fake()->unique()->numberBetween(1, 20),
            'color'     => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
