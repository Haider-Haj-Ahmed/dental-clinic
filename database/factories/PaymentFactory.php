<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        // Create invoice first, then derive patient_id from it
        // so payment.patient_id always matches invoice.patient_id
        return [
            'invoice_id'  => Invoice::factory(),
            'patient_id'  => function (array $attributes) {
                return Invoice::find($attributes['invoice_id'])?->patient_id
                    ?? Invoice::factory()->create()->patient_id;
            },
            'amount'      => $this->faker->numberBetween(1000, 50000),
            'method'      => $this->faker->randomElement(['cash', 'card', 'bank_transfer', 'insurance']),
            'paid_at'     => $this->faker->dateTimeBetween('-30 days', 'now'),
            'notes'       => $this->faker->optional(.2)->sentence(),
            'recorded_by' => User::factory(),
        ];
    }

    public function cash(): static
    {
        return $this->state(['method' => 'cash']);
    }

    public function card(): static
    {
        return $this->state(['method' => 'card']);
    }

    /**
     * Bind to a specific invoice — patient_id is derived automatically.
     */
    public function forInvoice(Invoice $invoice): static
    {
        return $this->state([
            'invoice_id' => $invoice->id,
            'patient_id' => $invoice->patient_id,
        ]);
    }
}
