<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("App.Models.User.{$this->appointment->created_by}"),
        ];

        if ($this->appointment->provider?->user_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->appointment->provider->user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'appointment.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id'  => $this->appointment->id,
            'patient_name'    => $this->appointment->patient->first_name . ' ' . $this->appointment->patient->last_name,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->appointment->status,
            'start_at'        => $this->appointment->start_at->toIso8601String(),
        ];
    }
}
