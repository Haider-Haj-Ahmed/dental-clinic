<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'  => Patient::factory(),
            'status'      => Invoice::STATUS_DRAFT,
            'issued_at'   => today()->toDateString(),
            'subtotal'    => 0,
            'discount'    => 0,
            'tax'         => 0,
            'total'       => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => Invoice::STATUS_DRAFT]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => [
            'status'       => Invoice::STATUS_FINALIZED,
            'finalized_by' => User::factory()->create(['role' => \App\Models\User::ROLE_OWNER])->id,
        ]);
    }

    public function withItems(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            $invoice->items()->create([
                'description' => 'Test item',
                'qty'         => 1,
                'unit_price'  => 100.00,
                'total'       => 100.00,
            ]);
            $invoice->recalculateTotals();
        });
    }
}
