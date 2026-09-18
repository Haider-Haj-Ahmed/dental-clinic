<?php

namespace App\Listeners;

use App\Events\RecallOverdue;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\WebhookDispatcher;

class NotifyOnRecallOverdue implements ShouldQueue
{
    public function handle(RecallOverdue $event): void
    {
        app(WebhookDispatcher::class)->dispatch('recall.overdue', [
            'recall_id'    => $event->recall->id,
            'patient_id'   => $event->recall->patient_id,
            'patient_name' => $event->recall->patient->first_name . ' ' . $event->recall->patient->last_name,
            'due_date'     => $event->recall->due_date->toDateString(),
        ]);

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
