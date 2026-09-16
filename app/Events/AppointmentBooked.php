<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentBooked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Appointment $appointment) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("App.Models.User.{$this->appointment->created_by}"),
        ];

        // Also notify the assigned provider if they have a user account
        if ($this->appointment->provider?->user_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->appointment->provider->user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'appointment.booked';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'patient_name'   => $this->appointment->patient->first_name . ' ' . $this->appointment->patient->last_name,
            'start_at'       => $this->appointment->start_at->toIso8601String(),
            'type'           => $this->appointment->appointmentType?->name,
        ];
    }
}
