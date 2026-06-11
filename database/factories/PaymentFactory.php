<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id'  => Invoice::factory()->finalized(),
            'patient_id'  => Patient::factory(),
            'amount'      => fake()->randomFloat(2, 10, 500),
            'paid_at'     => today()->toDateString(),
            'recorded_by' => User::factory(),
        ];
    }
}
