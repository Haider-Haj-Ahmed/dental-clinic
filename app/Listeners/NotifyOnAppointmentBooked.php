<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyOnAppointmentBooked implements ShouldQueue
{
    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $patientName = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
        $dateTime    = $appointment->start_at->format('D d M \a\t H:i');

        $notification = new InAppNotification(
            type:  'appointment.booked',
            title: 'New appointment booked',
            body:  "{$patientName} — {$dateTime}",
            data:  ['appointment_id' => $appointment->id],
            url:   "/dashboard/appointments/{$appointment->id}",
        );

        // Notify the receptionist/owner who booked it
        $booker = User::find($appointment->created_by);
        $booker?->notify($notification);

        // Notify the assigned provider if different from booker
        if ($appointment->provider?->user_id && $appointment->provider->user_id !== $appointment->created_by) {
            $provider = User::find($appointment->provider->user_id);
            $provider?->notify(new InAppNotification(
                type:  'appointment.booked',
                title: 'You have a new appointment',
                body:  "{$patientName} — {$dateTime}",
                data:  ['appointment_id' => $appointment->id],
                url:   "/dashboard/appointments/{$appointment->id}",
            ));
        }
    }
}
