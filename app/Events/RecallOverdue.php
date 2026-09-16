<?php

namespace App\Events;

use App\Models\Recall;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecallOverdue implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Recall $recall) {}

    /**
     * Broadcast to all owner users — overdue recalls are an admin concern.
     * In a multi-owner setup this would fan out; for now targets the first owner.
     */
    public function broadcastOn(): array
    {
        $owner = \App\Models\User::where('role', \App\Models\User::ROLE_OWNER)->first();

        return $owner
            ? [new PrivateChannel("App.Models.User.{$owner->id}")]
            : [];
    }

    public function broadcastAs(): string
    {
        return 'recall.overdue';
    }

    public function broadcastWith(): array
    {
        return [
            'recall_id'    => $this->recall->id,
            'patient_name' => $this->recall->patient->first_name . ' ' . $this->recall->patient->last_name,
            'due_date'     => $this->recall->due_date->toDateString(),
            'days_overdue' => now()->diffInDays($this->recall->due_date),
        ];
    }
}
