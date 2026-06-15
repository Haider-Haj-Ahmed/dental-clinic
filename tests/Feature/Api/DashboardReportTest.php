<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $receptionist;
    private User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        $this->provider     = User::factory()->create(['role' => User::ROLE_PROVIDER]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_provider_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->provider);
        $this->getJson('/api/v1/dashboard/kpis')->assertForbidden();
    }

    public function test_provider_cannot_access_reports(): void
    {
        Sanctum::actingAs($this->provider);
        $this->getJson('/api/v1/reports/appointments')->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard/kpis')->assertUnauthorized();
    }

    // ── Dashboard KPIs ────────────────────────────────────────────────────────

    public function test_dashboard_kpis_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/dashboard/kpis')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'today'  => ['appointments_total', 'appointments_completed', 'appointments_remaining'],
                    'month'  => ['revenue_collected', 'invoices_created', 'new_patients'],
                    'totals' => ['active_patients', 'pending_recalls', 'low_stock_items', 'outstanding_balance'],
                ],
            ]);
    }

    public function test_dashboard_counts_todays_appointments(): void
    {
        Sanctum::actingAs($this->owner);
        $providerModel = Provider::factory()->create();
        $patient       = Patient::factory()->create();

        Appointment::factory()->create([
            'patient_id'  => $patient->id,
            'provider_id' => $providerModel->id,
            'start_at'    => today()->setHour(9),
            'end_at'      => today()->setHour(10),
            'status'      => Appointment::STATUS_COMPLETED,
            'created_by'  => $this->owner->id,
        ]);

        Appointment::factory()->create([
            'patient_id'  => $patient->id,
            'provider_id' => $providerModel->id,
            'start_at'    => today()->setHour(11),
            'end_at'      => today()->setHour(12),
            'status'      => Appointment::STATUS_SCHEDULED,
            'created_by'  => $this->owner->id,
        ]);

        $data = $this->getJson('/api/v1/dashboard/kpis')->json('data.today');

        $this->assertEquals(2, $data['appointments_total']);
        $this->assertEquals(1, $data['appointments_completed']);
        $this->assertEquals(1, $data['appointments_remaining']);
    }

    public function test_dashboard_counts_pending_recalls_due_today_or_earlier(): void
    {
        Sanctum::actingAs($this->owner);
        $patient = Patient::factory()->create();

        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today()->subDay(),  'status' => Recall::STATUS_PENDING]);
        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today()->addMonth(), 'status' => Recall::STATUS_PENDING]);

        $data = $this->getJson('/api/v1/dashboard/kpis')->json('data.totals');

        $this->assertEquals(1, $data['pending_recalls']);
    }

    public function test_dashboard_counts_low_stock_items(): void
    {
        Sanctum::actingAs($this->owner);

        InventoryItem::factory()->create(['current_stock' => 2,  'reorder_level' => 10, 'is_active' => true]);
        InventoryItem::factory()->create(['current_stock' => 50, 'reorder_level' => 10, 'is_active' => true]);

        $data = $this->getJson('/api/v1/dashboard/kpis')->json('data.totals');

        $this->assertEquals(1, $data['low_stock_items']);
    }

    public function test_receptionist_can_access_dashboard(): void
    {
        Sanctum::actingAs($this->receptionist);
        $this->getJson('/api/v1/dashboard/kpis')->assertOk();
    }

    // ── Appointments report ───────────────────────────────────────────────────

    public function test_appointments_report_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/reports/appointments')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['period', 'total', 'by_status', 'by_provider', 'by_day'],
            ]);
    }

    public function test_appointments_report_groups_by_status(): void
    {
        Sanctum::actingAs($this->owner);
        $providerModel = Provider::factory()->create();
        $patient       = Patient::factory()->create();

        Appointment::factory()->count(2)->create([
            'patient_id'  => $patient->id,
            'provider_id' => $providerModel->id,
            'start_at'    => today(),
            'end_at'      => today()->addHour(),
            'status'      => Appointment::STATUS_COMPLETED,
            'created_by'  => $this->owner->id,
        ]);

        Appointment::factory()->create([
            'patient_id'  => $patient->id,
            'provider_id' => $providerModel->id,
            'start_at'    => today(),
            'end_at'      => today()->addHour(),
            'status'      => Appointment::STATUS_CANCELLED,
            'created_by'  => $this->owner->id,
        ]);

        $data = $this->getJson('/api/v1/reports/appointments?date_from='.today()->toDateString().'&date_to='.today()->toDateString())
            ->assertOk()
            ->json('data');

        $this->assertEquals(3, $data['total']);
        $this->assertEquals(2, $data['by_status']['completed']);
        $this->assertEquals(1, $data['by_status']['cancelled']);
    }

    // ── Production report ─────────────────────────────────────────────────────

    public function test_production_report_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/reports/production')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['period', 'total_billed', 'by_provider', 'by_month'],
            ]);
    }

    public function test_production_report_sums_finalized_invoices(): void
    {
        Sanctum::actingAs($this->owner);
        $patient = Patient::factory()->create();

        Invoice::factory()->finalized()->create([
            'patient_id' => $patient->id,
            'total'      => 300.00,
            'subtotal'   => 300.00,
            'issued_at'  => today(),
        ]);

        Invoice::factory()->draft()->create([
            'patient_id' => $patient->id,
            'total'      => 100.00,
            'issued_at'  => today(),
        ]);

        $data = $this->getJson('/api/v1/reports/production?date_from='.today()->toDateString().'&date_to='.today()->toDateString())
            ->json('data');

        $this->assertEquals(300.00, $data['total_billed']);
    }

    // ── Collections report ────────────────────────────────────────────────────

    public function test_collections_report_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/reports/collections')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['period', 'total_collected', 'total_billed', 'collection_rate', 'by_payment_method', 'by_day'],
            ]);
    }

    public function test_collections_report_calculates_collection_rate(): void
    {
        Sanctum::actingAs($this->owner);
        $patient = Patient::factory()->create();

        $invoice = Invoice::factory()->finalized()->create([
            'patient_id' => $patient->id,
            'total'      => 200.00,
            'subtotal'   => 200.00,
            'issued_at'  => today(),
        ]);

        Payment::factory()->create([
            'invoice_id'  => $invoice->id,
            'patient_id'  => $patient->id,
            'amount'      => 100.00,
            'paid_at'     => today(),
            'recorded_by' => $this->owner->id,
        ]);

        $data = $this->getJson('/api/v1/reports/collections?date_from='.today()->toDateString().'&date_to='.today()->toDateString())
            ->json('data');

        $this->assertEquals(100.00, $data['total_collected']);
        $this->assertEquals(200.00, $data['total_billed']);
        $this->assertEquals(50.0,   $data['collection_rate']);
    }

    // ── Recall performance report ─────────────────────────────────────────────

    public function test_recall_performance_report_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/reports/recall-performance')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['period', 'total', 'by_status', 'conversion_rate', 'reminder_rate'],
            ]);
    }

    public function test_recall_conversion_rate_calculated_correctly(): void
    {
        Sanctum::actingAs($this->owner);
        $patient = Patient::factory()->create();

        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today(), 'status' => Recall::STATUS_BOOKED]);
        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today(), 'status' => Recall::STATUS_PENDING]);
        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today(), 'status' => Recall::STATUS_DISMISSED]);
        Recall::factory()->create(['patient_id' => $patient->id, 'due_date' => today(), 'status' => Recall::STATUS_BOOKED]);

        $data = $this->getJson('/api/v1/reports/recall-performance?date_from='.today()->toDateString().'&date_to='.today()->toDateString())
            ->json('data');

        $this->assertEquals(4,    $data['total']);
        $this->assertEquals(50.0, $data['conversion_rate']); // 2 booked out of 4
    }

    // ── Inventory report ──────────────────────────────────────────────────────

    public function test_inventory_report_returns_correct_structure(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/reports/inventory')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'summary'         => ['total_items', 'low_stock_count', 'total_stock_value'],
                    'low_stock_items',
                    'by_category',
                ],
            ]);
    }

    public function test_inventory_report_calculates_stock_value(): void
    {
        Sanctum::actingAs($this->owner);

        InventoryItem::factory()->create(['current_stock' => 10, 'unit_cost' => 5.00,  'is_active' => true, 'category' => 'PPE']);
        InventoryItem::factory()->create(['current_stock' => 5,  'unit_cost' => 20.00, 'is_active' => true, 'category' => 'PPE']);

        $data = $this->getJson('/api/v1/reports/inventory')->json('data');

        $this->assertEquals(150.00, $data['summary']['total_stock_value']); // (10*5) + (5*20)
        $this->assertEquals(2, $data['summary']['total_items']);
    }

    public function test_inventory_report_can_filter_by_category(): void
    {
        Sanctum::actingAs($this->owner);

        InventoryItem::factory()->create(['is_active' => true, 'category' => 'PPE']);
        InventoryItem::factory()->create(['is_active' => true, 'category' => 'consumables']);

        $data = $this->getJson('/api/v1/reports/inventory?category=PPE')->json('data');

        $this->assertEquals(1, $data['summary']['total_items']);
    }
}
