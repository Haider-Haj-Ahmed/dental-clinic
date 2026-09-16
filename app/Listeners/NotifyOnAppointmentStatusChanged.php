<?php

namespace App\Listeners;

use App\Events\AppointmentStatusChanged;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyOnAppointmentStatusChanged implements ShouldQueue
{
    public function handle(AppointmentStatusChanged $event): void
    {
        $appointment  = $event->appointment;
        $patientName  = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
        $statusLabel  = ucfirst($appointment->status);

        $notification = new InAppNotification(
            type:  'appointment.status_changed',
            title: "Appointment {$statusLabel}",
            body:  "{$patientName} — status changed from {$event->previousStatus} to {$appointment->status}",
            data:  [
                'appointment_id'  => $appointment->id,
                'previous_status' => $event->previousStatus,
                'new_status'      => $appointment->status,
            ],
            url:   "/dashboard/appointments/{$appointment->id}",
        );

        $booker = User::find($appointment->created_by);
        $booker?->notify($notification);

        if ($appointment->provider?->user_id && $appointment->provider->user_id !== $appointment->created_by) {
            User::find($appointment->provider->user_id)?->notify($notification);
        }
    }
}
