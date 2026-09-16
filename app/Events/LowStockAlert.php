<?php

namespace App\Events;

use App\Models\InventoryItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly InventoryItem $item) {}

    public function broadcastOn(): array
    {
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_OWNER)->first();

        return $owner
            ? [new PrivateChannel("App.Models.User.{$owner->id}")]
            : [];
    }

    public function broadcastAs(): string
    {
        return 'inventory.low_stock';
    }

    public function broadcastWith(): array
    {
        return [
            'item_id'       => $this->item->id,
            'item_name'     => $this->item->name,
            'current_stock' => $this->item->current_stock,
            'reorder_level' => $this->item->reorder_level,
        ];
    }
}
