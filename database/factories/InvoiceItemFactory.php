<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InvoiceItem> */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 3);
        $unitPrice = $this->faker->randomFloat(2, 20, 400);

        return [
            'invoice_id'        => Invoice::factory(),
            'procedure_code_id' => null,
            'description'       => $this->faker->sentence(3),
            'tooth_number'      => null,
            'qty'               => $qty,
            'unit_price'        => $unitPrice,
            'total'             => round($qty * $unitPrice, 2),
        ];
    }
}
