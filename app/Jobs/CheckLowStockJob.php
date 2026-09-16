<?php

namespace App\Jobs;

use App\Events\LowStockAlert;
use App\Models\InventoryItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled: daily at 08:30.
 * Finds active items at or below reorder level.
 * Fires LowStockAlert per item — listener notifies owner.
 */
class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function handle(): void
    {
        $items = InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->get();

        if ($items->isEmpty()) {
            Log::info('CheckLowStockJob: all stock levels healthy.');
            return;
        }

        foreach ($items as $item) {
            event(new LowStockAlert($item));
        }

        Log::info("CheckLowStockJob: fired low stock alert for {$items->count()} item(s).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CheckLowStockJob failed', ['error' => $e->getMessage()]);
    }
}
