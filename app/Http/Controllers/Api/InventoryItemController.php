<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\StockMovementResource;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class InventoryItemController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(InventoryItem::class, 'inventory_item');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = InventoryItem::query()
            ->with('supplier')
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim((string) $request->string('search'));
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%"));
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return InventoryItemResource::collection($items);
    }

    /** GET /inventory-items/low-stock */
    public function lowStock(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->with('supplier')
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return InventoryItemResource::collection($items);
    }

    public function store(StoreInventoryItemRequest $request): InventoryItemResource
    {
        return InventoryItemResource::make(
            InventoryItem::query()->create($request->validated())->load('supplier')
        );
    }

    public function show(InventoryItem $inventoryItem): InventoryItemResource
    {
        return InventoryItemResource::make($inventoryItem->load('supplier'));
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem): InventoryItemResource
    {
        $inventoryItem->update($request->validated());

        return InventoryItemResource::make($inventoryItem->refresh()->load('supplier'));
    }

    public function destroy(InventoryItem $inventoryItem): Response
    {
        abort_if($inventoryItem->stockMovements()->exists(), 422, 'Cannot delete an item with stock movement history.');

        $inventoryItem->delete();

        return response()->noContent();
    }

    /** POST /inventory-items/{inventoryItem}/adjust-stock */
    public function adjustStock(AdjustStockRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $this->authorize('update', $inventoryItem);

        $movement = DB::transaction(function () use ($request, $inventoryItem) {
            $quantity  = $request->integer('quantity');
            $newStock  = $inventoryItem->current_stock + $quantity;

            abort_if($newStock < 0, 422, 'Adjustment would result in negative stock.');

            $inventoryItem->update(['current_stock' => $newStock]);

            return $inventoryItem->stockMovements()->create([
                'performed_by'  => $request->user()->id,
                'movement_type' => $request->string('movement_type'),
                'quantity'      => $quantity,
                'stock_after'   => $newStock,
                'reason'        => $request->input('reason'),
                'reference'     => $request->input('reference'),
                'performed_at'  => $request->input('performed_at', now()),
            ]);
        });

        return $this->successResponse([
            'current_stock' => $inventoryItem->fresh()->current_stock,
            'movement'      => StockMovementResource::make($movement->load('performedBy')),
        ], 'Stock adjusted successfully.');
    }

    /** GET /inventory-items/{inventoryItem}/movements */
    public function movements(Request $request, InventoryItem $inventoryItem): AnonymousResourceCollection
    {
        $this->authorize('view', $inventoryItem);

        $movements = $inventoryItem->stockMovements()
            ->with('performedBy')
            ->when($request->filled('type'), fn ($q) => $q->where('movement_type', $request->string('type')))
            ->orderBy('performed_at', 'desc')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return StockMovementResource::collection($movements);
    }
}
