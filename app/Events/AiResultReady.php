<?php

namespace App\Events;

use App\Models\AiAnalysisResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiResultReady implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly AiAnalysisResult $result) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("App.Models.User.{$this->result->requested_by}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ai.result_ready';
    }

    public function broadcastWith(): array
    {
        return [
            'result_id'     => $this->result->id,
            'analysis_type' => $this->result->analysis_type,
            'patient_name'  => $this->result->patient->first_name . ' ' . $this->result->patient->last_name,
            'status'        => $this->result->status,
        ];
    }
}
