<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Mail\AppointmentReminderMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Notifications\InAppNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled: daily at 08:00.
 * Finds appointments in the next 24 hours with no reminder sent yet.
 * Notifies assigned provider + booking staff member.
 * Marks reminder_sent_at to prevent duplicate sends.
 */
class SendAppointmentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function handle(): void
    {
        $appointments = Appointment::query()
            ->with(['patient', 'provider', 'appointmentType'])
            ->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_CONFIRMED])
            ->whereBetween('start_at', [now()->addHours(1), now()->addHours(25)])
            ->whereNull('reminder_sent_at')
            ->get();

        if ($appointments->isEmpty()) {
            Log::info('SendAppointmentRemindersJob: no upcoming appointments to remind.');
            return;
        }

        foreach ($appointments as $appointment) {
            $patientName = $appointment->patient->first_name . ' ' . $appointment->patient->last_name;
            $dateTime    = $appointment->start_at->format('D d M \a\t H:i');
            $typeName    = $appointment->appointmentType?->name ?? 'Appointment';

            $notification = new InAppNotification(
                type:  'appointment.reminder',
                title: 'Appointment reminder',
                body:  "{$patientName} — {$typeName} {$dateTime}",
                data:  ['appointment_id' => $appointment->id],
                url:   "/dashboard/appointments/{$appointment->id}",
            );

            if ($appointment->provider?->user_id) {
                User::find($appointment->provider->user_id)?->notify($notification);
            }

            if ($appointment->created_by && $appointment->created_by !== $appointment->provider?->user_id) {
                User::find($appointment->created_by)?->notify($notification);
            }

            // Email patient if email on file
            if ($appointment->patient->email) {
                $timeframe = now()->diffInHours($appointment->start_at) <= 3 ? '2 hours' : '24 hours';
                Mail::to($appointment->patient->email, $appointment->patient->first_name)
                    ->queue(new AppointmentReminderMail($appointment, $timeframe));
            }

            $appointment->update(['reminder_sent_at' => now()]);
        }

        Log::info("SendAppointmentRemindersJob: reminded {$appointments->count()} appointment(s).");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendAppointmentRemindersJob failed', ['error' => $e->getMessage()]);
    }
}
