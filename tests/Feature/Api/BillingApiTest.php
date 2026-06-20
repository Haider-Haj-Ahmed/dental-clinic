<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $receptionist;
    private User $provider;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->provider     = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->patient      = Patient::factory()->create();
    }

    // ── Invoices ──────────────────────────────────────────────────────────────

    public function test_receptionist_can_create_invoice_with_items(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/invoices', [
            'patient_id' => $this->patient->id,
            'issued_at'  => today()->toDateString(),
            'items' => [
                ['description' => 'Filling #36', 'qty' => 1, 'unit_price' => 150.00],
                ['description' => 'X-Ray',        'qty' => 2, 'unit_price' => 25.00],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', Invoice::STATUS_DRAFT)
            ->assertJsonPath('data.subtotal', '200.00')
            ->assertJsonPath('data.total', '200.00')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_provider_cannot_create_invoice(): void
    {
        Sanctum::actingAs($this->provider);

        $this->postJson('/api/v1/invoices', [
            'patient_id' => $this->patient->id,
            'issued_at'  => today()->toDateString(),
            'items'      => [['description' => 'Test', 'qty' => 1, 'unit_price' => 100]],
        ])->assertForbidden();
    }

    public function test_invoice_requires_at_least_one_item(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/invoices', [
            'patient_id' => $this->patient->id,
            'issued_at'  => today()->toDateString(),
            'items'      => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');
    }

    public function test_draft_invoice_can_be_finalized(): void
    {
        Sanctum::actingAs($this->receptionist);

        $invoice = Invoice::factory()->draft()->withItems()->create(['patient_id' => $this->patient->id]);

        $this->postJson("/api/v1/invoices/{$invoice->id}/finalize")
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_FINALIZED);
    }

    public function test_finalized_invoice_cannot_be_edited(): void
    {
        Sanctum::actingAs($this->receptionist);

        $invoice = Invoice::factory()->finalized()->create(['patient_id' => $this->patient->id]);

        $this->putJson("/api/v1/invoices/{$invoice->id}", ['notes' => 'edit attempt'])
            ->assertUnprocessable();
    }

    public function test_invoice_can_be_voided(): void
    {
        Sanctum::actingAs($this->owner);

        $invoice = Invoice::factory()->finalized()->create(['patient_id' => $this->patient->id]);

        $this->postJson("/api/v1/invoices/{$invoice->id}/void")
            ->assertOk()
            ->assertJsonPath('data.status', Invoice::STATUS_VOID);
    }

    public function test_only_draft_invoices_can_be_deleted(): void
    {
        Sanctum::actingAs($this->owner);

        $finalized = Invoice::factory()->finalized()->create(['patient_id' => $this->patient->id]);

        $this->deleteJson("/api/v1/invoices/{$finalized->id}")->assertUnprocessable();
    }

    // ── Payments ──────────────────────────────────────────────────────────────

    public function test_payment_can_be_recorded_and_updates_invoice_status(): void
    {
        Sanctum::actingAs($this->receptionist);

        $invoice = Invoice::factory()->finalized()->create([
            'patient_id' => $this->patient->id,
            'total'      => 200.00,
        ]);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 200.00,
            'paid_at'    => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonPath('data.amount', '200.00');

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    public function test_partial_payment_sets_invoice_to_partial(): void
    {
        Sanctum::actingAs($this->receptionist);

        $invoice = Invoice::factory()->finalized()->create([
            'patient_id' => $this->patient->id,
            'total'      => 200.00,
        ]);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 100.00,
            'paid_at'    => today()->toDateString(),
        ])->assertCreated();

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => Invoice::STATUS_PARTIAL,
        ]);
    }

    public function test_cannot_pay_draft_invoice(): void
    {
        Sanctum::actingAs($this->receptionist);

        $invoice = Invoice::factory()->draft()->create(['patient_id' => $this->patient->id]);

        $this->postJson('/api/v1/payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 100.00,
            'paid_at'    => today()->toDateString(),
        ])->assertUnprocessable();
    }

    // ── Payment plans ─────────────────────────────────────────────────────────

    public function test_payment_plan_generates_installment_items(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/payment-plans', [
            'patient_id'   => $this->patient->id,
            'total_amount' => 300.00,
            'installments' => 3,
            'start_date'   => today()->toDateString(),
        ])->assertCreated()
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.items.0.amount', '100.00');
    }

    public function test_installments_must_be_at_least_2(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/payment-plans', [
            'patient_id'   => $this->patient->id,
            'total_amount' => 100.00,
            'installments' => 1,
            'start_date'   => today()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('installments');
    }

    // ── Ledger ────────────────────────────────────────────────────────────────

    public function test_patient_ledger_returns_summary(): void
    {
        Sanctum::actingAs($this->owner);

        $invoice = Invoice::factory()->finalized()->create([
            'patient_id' => $this->patient->id,
            'total'      => 500.00,
            'subtotal'   => 500.00,
        ]);

        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'patient_id' => $this->patient->id,
            'amount'     => 200.00,
            'paid_at'    => today()->toDateString(),
            'recorded_by'=> $this->owner->id,
        ]);

        $response = $this->getJson("/api/v1/patients/{$this->patient->id}/ledger")
            ->assertOk();

        $this->assertEquals(500.00, $response->json('summary.total_billed'));
        $this->assertEquals(200.00, $response->json('summary.total_paid'));
        $this->assertEquals(300.00, $response->json('summary.outstanding'));
    }
}
