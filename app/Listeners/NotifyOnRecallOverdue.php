<?php

namespace App\Listeners;

use App\Events\RecallOverdue;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyOnRecallOverdue implements ShouldQueue
{
    public function handle(RecallOverdue $event): void
    {
        $recall      = $event->recall;
        $patientName = $recall->patient->first_name . ' ' . $recall->patient->last_name;
        $daysOverdue = now()->diffInDays($recall->due_date);

        $owner = User::where('role', User::ROLE_OWNER)->first();
        $owner?->notify(new InAppNotification(
            type:  'recall.overdue',
            title: 'Recall overdue',
            body:  "{$patientName} — {$daysOverdue} day(s) overdue",
            data:  ['recall_id' => $recall->id],
            url:   "/dashboard/recalls/{$recall->id}",
        ));
    }
}
