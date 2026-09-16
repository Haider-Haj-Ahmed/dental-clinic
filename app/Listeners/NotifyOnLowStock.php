<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyOnLowStock implements ShouldQueue
{
    public function handle(LowStockAlert $event): void
    {
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
