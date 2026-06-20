<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryItem> */
class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        $stock   = fake()->numberBetween(0, 100);
        $reorder = fake()->numberBetween(5, 20);

        return [
            'name'          => fake()->words(3, true),
            'sku'           => strtoupper(fake()->bothify('??-#####')),
            'category'      => fake()->randomElement(['consumables', 'instruments', 'medication', 'PPE']),
            'unit'          => fake()->randomElement(['piece', 'box', 'ml', 'g']),
            'current_stock' => $stock,
            'reorder_level' => $reorder,
            'unit_cost'     => fake()->randomFloat(2, 1, 200),
            'is_active'     => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'current_stock' => 2,
            'reorder_level' => 10,
        ]);
    }
}
