<?php

namespace Tests\Feature\Api;

use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryApiTest extends TestCase
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

    // ── Inventory items ───────────────────────────────────────────────────────

    public function test_receptionist_can_create_inventory_item(): void
    {
        Sanctum::actingAs($this->receptionist);

        $this->postJson('/api/v1/inventory-items', [
            'name'          => 'Dental Gloves',
            'sku'           => 'GL-001',
            'category'      => 'PPE',
            'unit'          => 'box',
            'current_stock' => 50,
            'reorder_level' => 10,
            'unit_cost'     => 12.50,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Dental Gloves')
            ->assertJsonPath('data.is_low_stock', false);
    }

    public function test_provider_cannot_create_inventory_item(): void
    {
        Sanctum::actingAs($this->provider);

        $this->postJson('/api/v1/inventory-items', [
            'name' => 'Gloves',
        ])->assertForbidden();
    }

    public function test_provider_can_view_inventory_items(): void
    {
        InventoryItem::factory()->count(3)->create();
        Sanctum::actingAs($this->provider);

        $this->getJson('/api/v1/inventory-items')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_low_stock_endpoint_returns_only_low_items(): void
    {
        Sanctum::actingAs($this->owner);

        InventoryItem::factory()->lowStock()->create();
        InventoryItem::factory()->create(['current_stock' => 100, 'reorder_level' => 10]);

        $this->getJson('/api/v1/inventory-items/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_low_stock', true);
    }

    public function test_duplicate_sku_rejected(): void
    {
        Sanctum::actingAs($this->owner);
        InventoryItem::factory()->create(['sku' => 'GL-001']);

        $this->postJson('/api/v1/inventory-items', [
            'name' => 'Other Item',
            'sku'  => 'GL-001',
        ])->assertUnprocessable()->assertJsonValidationErrors('sku');
    }

    // ── Stock adjustment ──────────────────────────────────────────────────────

    public function test_stock_can_be_adjusted_in(): void
    {
        Sanctum::actingAs($this->receptionist);
        $item = InventoryItem::factory()->create(['current_stock' => 20]);

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust-stock", [
            'movement_type' => 'in',
            'quantity'      => 10,
            'reason'        => 'Restock from supplier',
        ])->assertOk()
            ->assertJsonPath('current_stock', 30)
            ->assertJsonPath('movement.movement_type', 'in')
            ->assertJsonPath('movement.quantity', 10)
            ->assertJsonPath('movement.stock_after', 30);
    }

    public function test_stock_can_be_adjusted_out(): void
    {
        Sanctum::actingAs($this->receptionist);
        $item = InventoryItem::factory()->create(['current_stock' => 20]);

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust-stock", [
            'movement_type' => 'out',
            'quantity'      => -5,
            'reason'        => 'Used in procedure',
        ])->assertOk()
            ->assertJsonPath('current_stock', 15);
    }

    public function test_adjustment_cannot_result_in_negative_stock(): void
    {
        Sanctum::actingAs($this->receptionist);
        $item = InventoryItem::factory()->create(['current_stock' => 5]);

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust-stock", [
            'movement_type' => 'out',
            'quantity'      => -10,
        ])->assertUnprocessable();
    }

    public function test_zero_quantity_is_rejected(): void
    {
        Sanctum::actingAs($this->receptionist);
        $item = InventoryItem::factory()->create();

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust-stock", [
            'movement_type' => 'adjustment',
            'quantity'      => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors('quantity');
    }

    // ── Suppliers ─────────────────────────────────────────────────────────────

    public function test_owner_can_create_supplier(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/v1/suppliers', [
            'name'  => 'MedDent Supplies Ltd',
            'email' => 'orders@meddent.com',
            'phone' => '+96170123456',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'MedDent Supplies Ltd');
    }

    public function test_supplier_with_orders_cannot_be_deleted(): void
    {
        Sanctum::actingAs($this->owner);
        $supplier = Supplier::factory()->create();
        PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by'  => $this->owner->id,
        ]);

        $this->deleteJson("/api/v1/suppliers/{$supplier->id}")->assertUnprocessable();
    }

    // ── Purchase orders ───────────────────────────────────────────────────────

    public function test_receptionist_can_create_purchase_order(): void
    {
        Sanctum::actingAs($this->receptionist);
        $supplier = Supplier::factory()->create();
        $item     = InventoryItem::factory()->create(['current_stock' => 5]);

        $this->postJson('/api/v1/purchase-orders', [
            'supplier_id' => $supplier->id,
            'ordered_at'  => today()->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'quantity_ordered'  => 20,
                    'unit_cost'         => 10.00,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', PurchaseOrder::STATUS_DRAFT)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_receiving_order_increments_stock_and_logs_movement(): void
    {
        Sanctum::actingAs($this->owner);
        $supplier = Supplier::factory()->create();
        $item     = InventoryItem::factory()->create(['current_stock' => 5]);

        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by'  => $this->owner->id,
            'status'      => PurchaseOrder::STATUS_SENT,
        ]);

        $orderItem = $order->items()->create([
            'inventory_item_id' => $item->id,
            'quantity_ordered'  => 20,
            'unit_cost'         => 10.00,
        ]);

        $this->postJson("/api/v1/purchase-orders/{$order->id}/receive", [
            'items' => [
                ['id' => $orderItem->id, 'quantity_received' => 20],
            ],
        ])->assertOk()
            ->assertJsonPath('data.status', PurchaseOrder::STATUS_RECEIVED);

        $this->assertDatabaseHas('inventory_items', [
            'id'            => $item->id,
            'current_stock' => 25,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'movement_type'     => 'in',
            'quantity'          => 20,
            'stock_after'       => 25,
        ]);
    }

    public function test_cannot_receive_already_received_order(): void
    {
        Sanctum::actingAs($this->owner);
        $supplier = Supplier::factory()->create();
        $order    = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by'  => $this->owner->id,
            'status'      => PurchaseOrder::STATUS_RECEIVED,
        ]);

        $this->postJson("/api/v1/purchase-orders/{$order->id}/receive", ['items' => []])
            ->assertUnprocessable();
    }

    public function test_draft_order_can_be_cancelled_and_deleted(): void
    {
        Sanctum::actingAs($this->owner);
        $supplier = Supplier::factory()->create();
        $order    = PurchaseOrder::factory()->create([
            'supplier_id' => $supplier->id,
            'created_by'  => $this->owner->id,
            'status'      => PurchaseOrder::STATUS_DRAFT,
        ]);

        $this->deleteJson("/api/v1/purchase-orders/{$order->id}")->assertNoContent();
        $this->assertDatabaseMissing('purchase_orders', ['id' => $order->id]);
    }
}
