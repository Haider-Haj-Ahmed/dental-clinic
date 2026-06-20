<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseOrder> */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'created_by'  => User::factory(),
            'status'      => PurchaseOrder::STATUS_DRAFT,
            'ordered_at'  => null,
            'expected_at' => null,
            'received_at' => null,
        ];
    }
}
