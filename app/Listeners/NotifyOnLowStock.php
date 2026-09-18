<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\WebhookDispatcher;

class NotifyOnLowStock implements ShouldQueue
{
    public function handle(LowStockAlert $event): void
    {
        app(WebhookDispatcher::class)->dispatch('inventory.low_stock', [
            'item_id'       => $event->item->id,
            'item_name'     => $event->item->name,
            'current_stock' => $event->item->current_stock,
            'reorder_level' => $event->item->reorder_level,
        ]);

        $item  = $event->item;
        $owner = User::where('role', User::ROLE_OWNER)->first();

        $owner?->notify(new InAppNotification(
            type:  'inventory.low_stock',
            title: 'Low stock alert',
            body:  "{$item->name} — {$item->current_stock} {$item->unit}(s) remaining (reorder at {$item->reorder_level})",
            data:  ['item_id' => $item->id],
            url:   "/dashboard/inventory/{$item->id}",
        ));
    }
}
