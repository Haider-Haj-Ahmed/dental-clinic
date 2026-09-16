<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * InAppNotification
 *
 * Single reusable notification class for all in-app (database) notifications.
 * All events route through this — keeps the notifications table consistent.
 *
 * Usage:
 *   $user->notify(new InAppNotification(
 *       type:    'appointment.booked',
 *       title:   'New appointment booked',
 *       body:    'Ahmad Khalil — tomorrow at 09:00',
 *       data:    ['appointment_id' => 42],
 *       url:     '/dashboard/appointments/42',
 *   ));
 */
class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $body,
        private readonly array  $data = [],
        private readonly ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'  => $this->type,
            'title' => $this->title,
            'body'  => $this->body,
            'url'   => $this->url,
            'data'  => $this->data,
        ];
    }
}
