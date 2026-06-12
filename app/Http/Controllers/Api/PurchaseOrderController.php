<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PurchaseOrder::class, 'purchase_order');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'creator'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return PurchaseOrderResource::collection($orders);
    }

    public function store(StorePurchaseOrderRequest $request): PurchaseOrderResource
    {
        $order = DB::transaction(function () use ($request) {
            $order = PurchaseOrder::query()->create([
                ...$request->safe()->except('items'),
                'created_by' => $request->user()->id,
            ]);

            foreach ($request->input('items') as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        return PurchaseOrderResource::make($order->load(['supplier', 'creator', 'items.inventoryItem']));
    }

    public function show(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        return PurchaseOrderResource::make(
            $purchaseOrder->load(['supplier', 'creator', 'items.inventoryItem'])
        );
    }

    /** POST /purchase-orders/{purchaseOrder}/receive
     *  Marks order as received and increments stock for each item.
     */
    public function receive(Request $request, PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('update', $purchaseOrder);

        abort_if($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED, 422, 'Order already received.');
        abort_if($purchaseOrder->status === PurchaseOrder::STATUS_CANCELLED, 422, 'Cannot receive a cancelled order.');

        $request->validate([
            'items'                    => ['required', 'array'],
            'items.*.id'               => ['required', 'integer', 'exists:purchase_order_items,id'],
            'items.*.quantity_received'=> ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            foreach ($request->input('items') as $incoming) {
                $item = $purchaseOrder->items()->findOrFail($incoming['id']);
                $qtyReceived = (int) $incoming['quantity_received'];

                $item->update(['quantity_received' => $qtyReceived]);

                if ($qtyReceived > 0) {
                    $inventoryItem = $item->inventoryItem;
                    $newStock      = $inventoryItem->current_stock + $qtyReceived;

                    $inventoryItem->update(['current_stock' => $newStock]);

                    $inventoryItem->stockMovements()->create([
                        'performed_by'  => $request->user()->id,
                        'movement_type' => StockMovement::TYPE_IN,
                        'quantity'      => $qtyReceived,
                        'stock_after'   => $newStock,
                        'reason'        => "Purchase order #{$purchaseOrder->id}",
                        'reference'     => "PO-{$purchaseOrder->id}",
                        'performed_at'  => now(),
                    ]);
                }
            }

            $purchaseOrder->update([
                'status'      => PurchaseOrder::STATUS_RECEIVED,
                'received_at' => today()->toDateString(),
            ]);
        });

        return PurchaseOrderResource::make(
            $purchaseOrder->refresh()->load(['supplier', 'creator', 'items.inventoryItem'])
        );
    }

    /** POST /purchase-orders/{purchaseOrder}/cancel */
    public function cancel(PurchaseOrder $purchaseOrder): PurchaseOrderResource
    {
        $this->authorize('update', $purchaseOrder);

        abort_if($purchaseOrder->status === PurchaseOrder::STATUS_RECEIVED, 422, 'Cannot cancel a received order.');
        abort_if($purchaseOrder->status === PurchaseOrder::STATUS_CANCELLED, 422, 'Order is already cancelled.');

        $purchaseOrder->update(['status' => PurchaseOrder::STATUS_CANCELLED]);

        return PurchaseOrderResource::make($purchaseOrder->refresh()->load(['supplier', 'creator', 'items']));
    }

    public function destroy(PurchaseOrder $purchaseOrder): Response
    {
        abort_if($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT, 422, 'Only draft orders can be deleted.');

        $purchaseOrder->delete();

        return response()->noContent();
    }
}
