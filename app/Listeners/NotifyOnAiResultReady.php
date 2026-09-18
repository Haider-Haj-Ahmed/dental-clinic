<?php

namespace App\Listeners;

use App\Events\AiResultReady;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\WebhookDispatcher;

class NotifyOnAiResultReady implements ShouldQueue
{
    public function handle(AiResultReady $event): void
    {
        app(WebhookDispatcher::class)->dispatch('ai.result_ready', [
            'result_id'     => $event->result->id,
            'analysis_type' => $event->result->analysis_type,
            'patient_id'    => $event->result->patient_id,
            'status'        => $event->result->status,
        ]);

        $result      = $event->result;
        $patientName = $result->patient->first_name . ' ' . $result->patient->last_name;
        $typeLabel   = match ($result->analysis_type) {
            'soap_suggestion'         => 'SOAP suggestion',
            'xray_analysis'           => 'X-ray analysis',
            'prescription_suggestion' => 'Prescription suggestion',
            'perio_risk'              => 'Perio risk score',
            default                   => 'AI result',
        };

        $requestedBy = User::find($result->requested_by);
        $requestedBy?->notify(new InAppNotification(
            type:  'ai.result_ready',
            title: "AI result ready — {$typeLabel}",
            body:  "Analysis for {$patientName} is ready for your review.",
            data:  [
                'result_id'     => $result->id,
                'analysis_type' => $result->analysis_type,
            ],
            url:   "/dashboard/ai/{$result->id}",
        ));
    }
}
